<?php
require_once __DIR__ . '/includes/bootstrap.php';

$cats = q_all('SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id AND p.active = 1) AS n FROM categories c WHERE c.active = 1 ORDER BY c.sort, c.id');
$catSlug = qin('cat');
$search = mb_substr(qin('q'), 0, 80);
$sort = qin('sort', 'featured');
$cat = null;
foreach ($cats as $c) if ($c['slug'] === $catSlug) $cat = $c;

$where = ['p.active = 1', '(c.id IS NULL OR c.active = 1)'];
$params = [];
if ($cat) { $where[] = 'p.category_id = ?'; $params[] = $cat['id']; }
if ($search !== '') { $where[] = '(p.name LIKE ? OR p.short_desc LIKE ? OR p.description LIKE ?)'; $like = '%' . $search . '%'; array_push($params, $like, $like, $like); }
$w = implode(' AND ', $where);
$order = [
    'featured' => 'featured DESC, sort, id',
    'newest' => 'created_at DESC, id DESC',
    'price_asc' => 'CASE WHEN eff_price > 0 THEN 0 ELSE 1 END, eff_price ASC, id',
    'price_desc' => 'eff_price DESC, id',
    'name' => 'name ASC',
][$sort] ?? 'featured DESC, sort, id';

$total = (int) q_val("SELECT COUNT(*) FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE $w", $params);
$pg = paginate($total, 12, max(1, (int) qin('page', '1')));
$products = with_variants(q_all(
    "SELECT * FROM (SELECT p.*, c.name AS category_name, COALESCE((SELECT MIN(v.price) FROM product_variants v WHERE v.product_id = p.id), p.price) AS eff_price
     FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE $w) x ORDER BY $order LIMIT {$pg['per']} OFFSET {$pg['offset']}",
    $params
));

$title = ($cat ? $cat['name'] . ' — ' : '') . 'Shop | ' . setting('business_name');
$desc = $cat ? ($cat['description'] ?: 'Shop ' . $cat['name'] . ' from Mojhometouch.') : 'Shop Moj Paints, WPC fluted panels, PVC marble sheets, mirrors, clocks and décor online from Mojhometouch. Delivery across Nigeria.';
$active = 'shop';
if ($cat || $search !== '' || $sort !== 'featured') $canonical_query = $cat ? 'cat=' . rawurlencode($cat['slug']) : null;
require __DIR__ . '/includes/layout/header.php';
echo page_hero($cat ? '<a href="' . e(url('shop.php')) . '">Shop</a><span>/</span><span>' . e($cat['name']) . '</span>' : '<span>Shop</span>',
    $cat ? e($cat['name']) : 'Shop <em class="gold-text">Online</em>',
    $cat ? e($cat['description']) : 'Moj Paints, wall panels, marble sheets and décor — order online, pay securely and track your delivery.',
    $cat && $cat['image'] ? $cat['image'] : 'assets/img/wpc-fluted-panels.jpg', true);
?>
<section class="section" style="padding-top:44px"><div class="wrap">
  <div class="shop-bar">
    <nav class="cats" aria-label="Categories">
      <a href="<?= e(url('shop.php')) ?>"<?= !$cat ? ' class="on"' : '' ?>>All</a>
      <?php foreach ($cats as $c): if (!$c['n']) continue; ?>
        <a href="<?= e(url('shop.php?cat=' . rawurlencode($c['slug']))) ?>"<?= $cat && $cat['id'] === $c['id'] ? ' class="on"' : '' ?>><?= e($c['name']) ?></a>
      <?php endforeach; ?>
    </nav>
    <form class="shop-tools" method="get" role="search">
      <?php if ($cat): ?><input type="hidden" name="cat" value="<?= e($cat['slug']) ?>"><?php endif; ?>
      <label class="search"><span class="sr">Search products</span><?= ic('search') ?><input type="search" name="q" value="<?= e($search) ?>" placeholder="Search products…"></label>
      <label><span class="sr">Sort</span><select class="select" name="sort" onchange="this.form.submit()">
        <?php foreach (['featured' => 'Featured', 'newest' => 'Newest', 'price_asc' => 'Price: low to high', 'price_desc' => 'Price: high to low', 'name' => 'Name A–Z'] as $k => $l): ?>
          <option value="<?= $k ?>"<?= $sort === $k ? ' selected' : '' ?>><?= $l ?></option>
        <?php endforeach; ?>
      </select></label>
    </form>
  </div>
  <?php if ($search !== ''): ?><p class="result-note"><?= $total ?> result<?= $total === 1 ? '' : 's' ?> for “<?= e($search) ?>” · <a class="link" href="<?= e(url('shop.php' . ($cat ? '?cat=' . rawurlencode($cat['slug']) : ''))) ?>">Clear search</a></p><?php endif; ?>
  <?php if ($products): ?>
    <div class="pgrid"><?php foreach ($products as $p) echo product_card($p); ?></div>
    <?= page_links($pg, ['cat' => $cat['slug'] ?? '', 'q' => $search, 'sort' => $sort !== 'featured' ? $sort : '']) ?>
  <?php else: ?>
    <div class="empty"><?= ic('search') ?><h3>No products found</h3><p>Try a different search, or ask us — we may have it in the showroom.</p>
      <a class="btn btn-dark" href="<?= e(url('shop.php')) ?>">View all products</a> <a class="btn btn-wa" href="<?= e(wa_link('Hello Mojhometouch, do you have ' . ($search ?: 'this item') . '?')) ?>" target="_blank" rel="noopener"><?= wa_icon() ?> Ask on WhatsApp</a></div>
  <?php endif; ?>
  <div style="margin-top:56px"><?= trust_row() ?></div>
</div></section>
<?= cta_block('Need it installed or applied?', 'Our crews install panels, apply paint and style your space — request a quote.') ?>
<?php require __DIR__ . '/includes/layout/footer.php';
