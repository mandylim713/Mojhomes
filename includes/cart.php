<?php
// ---------- Catalogue lookups ----------
function product_find(int $id, bool $activeOnly = true): ?array
{
    return q_one('SELECT * FROM products WHERE id = ?' . ($activeOnly ? ' AND active = 1' : ''), [$id]);
}

function product_by_slug(string $slug): ?array
{
    return q_one('SELECT p.*, c.name AS category_name, c.slug AS category_slug FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE p.slug = ? AND p.active = 1', [$slug]);
}

function product_variants(int $pid): array
{
    return q_all('SELECT * FROM product_variants WHERE product_id = ? ORDER BY sort, id', [$pid]);
}

/** Lowest price shown on cards ("From ₦..."). 0 = price on request. */
function product_price_info(array $p): array
{
    $vars = $p['_variants'] ?? product_variants((int) $p['id']);
    if ($vars) {
        $prices = array_map(fn($v) => (int) $v['price'], $vars);
        $min = min($prices);
        return ['min' => $min, 'max' => max($prices), 'from' => count(array_unique($prices)) > 1];
    }
    return ['min' => (int) $p['price'], 'max' => (int) $p['price'], 'from' => false];
}

function product_in_stock(array $p): bool
{
    $vars = $p['_variants'] ?? product_variants((int) $p['id']);
    if ($vars) {
        foreach ($vars as $v) if ($v['stock'] === null || (int) $v['stock'] > 0) return true;
        return false;
    }
    return $p['stock'] === null || (int) $p['stock'] > 0;
}

function product_images(array $p): array
{
    $imgs = [];
    if (!empty($p['image'])) $imgs[] = $p['image'];
    $extra = json_decode((string) ($p['images'] ?? '[]'), true);
    if (is_array($extra)) foreach ($extra as $x) if (is_string($x) && $x !== '') $imgs[] = $x;
    return $imgs ?: [null];
}

/** Attach variants to a list of products in one query. */
function with_variants(array $products): array
{
    if (!$products) return $products;
    $ids = array_map(fn($p) => (int) $p['id'], $products);
    $rows = q_all('SELECT * FROM product_variants WHERE product_id IN (' . implode(',', array_fill(0, count($ids), '?')) . ') ORDER BY sort, id', $ids);
    $by = [];
    foreach ($rows as $r) $by[(int) $r['product_id']][] = $r;
    foreach ($products as &$p) $p['_variants'] = $by[(int) $p['id']] ?? [];
    return $products;
}

// ---------- Cart (session) ----------
function cart_raw(): array
{
    return (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) ? $_SESSION['cart'] : [];
}

function cart_key(int $pid, int $vid, string $opt): string
{
    return $pid . '-' . $vid . '-' . substr(md5(mb_strtolower(trim($opt))), 0, 8);
}

function cart_count(): int
{
    $n = 0;
    foreach (cart_raw() as $it) $n += (int) ($it['q'] ?? 0);
    return $n;
}

/** @return array{0:bool,1:string} */
function cart_add(int $pid, int $vid, string $opt, int $qty): array
{
    $qty = max(1, min(999, $qty));
    $p = product_find($pid);
    if (!$p) return [false, 'Sorry, this product is no longer available.'];
    $vars = product_variants($pid);
    $variant = null;
    if ($vars) {
        foreach ($vars as $v) if ((int) $v['id'] === $vid) $variant = $v;
        if (!$variant) return [false, 'Please choose a size / option first.'];
        $price = (int) $variant['price'];
        $stock = $variant['stock'];
    } else {
        $vid = 0;
        $price = (int) $p['price'];
        $stock = $p['stock'];
    }
    if ($price <= 0) return [false, 'Please contact us for the price of this item.'];
    $opt = mb_substr(trim(strip_tags($opt)), 0, 120);

    $cart = cart_raw();
    if ($stock !== null) {
        $already = 0;
        foreach ($cart as $it) if ((int) $it['p'] === $pid && (int) $it['v'] === $vid) $already += (int) $it['q'];
        $left = (int) $stock - $already;
        if ($left <= 0) return [false, $already ? 'You already have all available stock of this item in your cart.' : 'Sorry, this item is out of stock.'];
        if ($qty > $left) return [false, 'Only ' . $left . ' more available.'];
    }
    $key = cart_key($pid, $vid, $opt);
    $cart[$key] = ['p' => $pid, 'v' => $vid, 'o' => $opt, 'q' => ($cart[$key]['q'] ?? 0) + $qty];
    $_SESSION['cart'] = $cart;
    return [true, $p['name'] . ($variant ? ' (' . $variant['name'] . ')' : '') . ' added to your cart.'];
}

function cart_set_qty(string $key, int $qty): ?string
{
    $cart = cart_raw();
    if (!isset($cart[$key])) return null;
    if ($qty <= 0) {
        unset($cart[$key]);
        $_SESSION['cart'] = $cart;
        return null;
    }
    $qty = min(999, $qty);
    $it = $cart[$key];
    $stock = $it['v'] ? q_val('SELECT stock FROM product_variants WHERE id = ?', [$it['v']]) : q_val('SELECT stock FROM products WHERE id = ?', [$it['p']]);
    $msg = null;
    if ($stock !== null && $stock !== false) {
        $other = 0;
        foreach ($cart as $k => $x) if ($k !== $key && (int) $x['p'] === (int) $it['p'] && (int) $x['v'] === (int) $it['v']) $other += (int) $x['q'];
        $max = max(0, (int) $stock - $other);
        if ($qty > $max) {
            $qty = $max;
            $msg = 'Only ' . $max . ' available — quantity adjusted.';
        }
    }
    if ($qty <= 0) unset($cart[$key]);
    else $cart[$key]['q'] = $qty;
    $_SESSION['cart'] = $cart;
    return $msg;
}

function cart_remove(string $key): void
{
    $cart = cart_raw();
    unset($cart[$key]);
    $_SESSION['cart'] = $cart;
}

function cart_clear(): void
{
    $_SESSION['cart'] = [];
}

/**
 * Fresh cart lines priced from the database (never trust session prices).
 * Removes items that are no longer available.
 */
function cart_lines(): array
{
    $cart = cart_raw();
    $lines = [];
    $issues = [];
    $subtotal = 0;
    $changed = false;
    foreach ($cart as $key => $it) {
        $p = product_find((int) $it['p']);
        $variant = null;
        if ($p && (int) $it['v']) {
            $variant = q_one('SELECT * FROM product_variants WHERE id = ? AND product_id = ?', [(int) $it['v'], (int) $p['id']]);
        }
        $hasVars = $p ? (bool) q_val('SELECT COUNT(*) FROM product_variants WHERE product_id = ?', [(int) $p['id']]) : false;
        if (!$p || ((int) $it['v'] && !$variant) || (!(int) $it['v'] && $hasVars)) {
            $issues[] = 'An item in your cart is no longer available and was removed.';
            unset($cart[$key]);
            $changed = true;
            continue;
        }
        $price = $variant ? (int) $variant['price'] : (int) $p['price'];
        $stock = $variant ? $variant['stock'] : $p['stock'];
        if ($price <= 0) {
            $issues[] = $p['name'] . ' is now price-on-request and was removed from your cart.';
            unset($cart[$key]);
            $changed = true;
            continue;
        }
        $qty = (int) $it['q'];
        if ($stock !== null && $qty > (int) $stock) {
            $qty = (int) $stock;
            if ($qty <= 0) {
                $issues[] = $p['name'] . ' is now out of stock and was removed.';
                unset($cart[$key]);
                $changed = true;
                continue;
            }
            $issues[] = 'Only ' . $qty . ' of ' . $p['name'] . ' available — quantity adjusted.';
            $cart[$key]['q'] = $qty;
            $changed = true;
        }
        $line = $price * $qty;
        $subtotal += $line;
        $lines[] = [
            'key' => $key, 'product_id' => (int) $p['id'], 'variant_id' => $variant ? (int) $variant['id'] : 0,
            'name' => $p['name'], 'slug' => $p['slug'], 'image' => $p['image'], 'unit' => $p['unit'],
            'variant_name' => $variant['name'] ?? null, 'option_label' => $p['option_label'], 'option' => (string) $it['o'],
            'unit_price' => $price, 'qty' => $qty, 'line_total' => $line, 'stock' => $stock === null ? null : (int) $stock,
        ];
    }
    if ($changed) $_SESSION['cart'] = $cart;
    return ['lines' => $lines, 'subtotal' => $subtotal, 'issues' => $issues, 'count' => array_sum(array_column($lines, 'qty'))];
}
