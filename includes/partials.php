<?php
function page_hero(string $crumb, string $title, string $sub, string $bg = 'assets/img/pop-ceiling-sitting-room.jpg', bool $slim = false): string
{
    return '<section class="page-hero' . ($slim ? ' slim' : '') . '"><div class="ph-bg" aria-hidden="true" style="background-image:url(\'' . e(url($bg)) . '\')"></div><div class="wrap">'
        . '<div class="crumbs"><a href="' . e(url()) . '">Home</a><span>/</span>' . $crumb . '</div>'
        . '<h1>' . $title . '</h1>' . ($sub !== '' ? '<p>' . $sub . '</p>' : '') . '</div></section>';
}

function cta_block(string $title = 'Ready to transform your space?', string $sub = 'Tell us about your project — we\'ll visit, measure and send a clear quote.'): string
{
    return '<section class="section tight"><div class="wrap"><div class="cta rv"><div><h2>' . e($title) . '</h2><p>' . e($sub) . '</p></div>'
        . '<div class="btns"><a class="btn btn-dark" href="' . e(url('contact.php#quote')) . '">Request a Quote ' . ic('arrow') . '</a>'
        . '<a class="btn btn-wa" href="' . e(wa_link('Hello Mojhometouch, I\'d like a quote for a project.')) . '" target="_blank" rel="noopener">' . wa_icon() . ' WhatsApp Us</a></div></div></div></section>';
}

function product_card(array $p): string
{
    $pi = product_price_info($p);
    $in = product_in_stock($p);
    $hasVars = !empty($p['_variants']);
    $href = url('product.php?slug=' . rawurlencode($p['slug']));
    $tags = '';
    if (!$in) $tags .= '<span class="tag r">Sold out</span>';
    elseif ($p['compare_price'] && (int) $p['compare_price'] > $pi['min'] && !$hasVars) $tags .= '<span class="tag r">Sale</span>';
    if ($p['featured'] && $in) $tags .= '<span class="tag y">Bestseller</span>';
    if ($pi['min'] <= 0) {
        $price = '<span class="price req">Price on request</span>';
        $btn = '<a class="qadd" href="' . e($href) . '" aria-label="View ' . e($p['name']) . '">' . ic('arrow') . '</a>';
    } else {
        $price = '<span class="price">' . ($pi['from'] ? '<small>From</small>' : ($p['unit'] ? '<small>' . e($p['unit']) . '</small>' : '')) . money($pi['min'])
            . ((!$hasVars && $p['compare_price'] && (int) $p['compare_price'] > $pi['min']) ? '<s>' . money($p['compare_price']) . '</s>' : '') . '</span>';
        if ($in && !$hasVars && empty($p['option_label'])) {
            $btn = '<form method="post" action="' . e(url('cart.php')) . '" class="js-add">' . csrf_field()
                . '<input type="hidden" name="action" value="add"><input type="hidden" name="product_id" value="' . (int) $p['id'] . '"><input type="hidden" name="qty" value="1">'
                . '<button class="qadd" type="submit" aria-label="Add ' . e($p['name']) . ' to cart">' . ic('plus') . '</button></form>';
        } else {
            $btn = '<a class="qadd" href="' . e($href) . '" aria-label="Choose options for ' . e($p['name']) . '">' . ic('arrow') . '</a>';
        }
    }
    return '<article class="pcard rv"><a class="ph" href="' . e($href) . '"><div class="tags">' . $tags . '</div>'
        . '<img src="' . e(img_url($p['image'])) . '" alt="' . e($p['name']) . '" loading="lazy"></a>'
        . '<div class="bd">' . (!empty($p['category_name']) ? '<span class="cat">' . e($p['category_name']) . '</span>' : '')
        . '<h3><a href="' . e($href) . '">' . e($p['name']) . '</a></h3><p>' . e($p['short_desc']) . '</p>'
        . '<div class="row">' . $price . $btn . '</div></div></article>';
}

function strip_html(): string
{
    $items = ['Moj Paints', 'Professional Painting', 'POP Ceilings', 'Screeding', 'Curtains & Blinds', 'Furniture & Cabinetry', 'Epoxy Flooring', 'Stamp Concrete', 'Mirrors & Frames', 'Interior Décor'];
    $s = '';
    foreach (array_merge($items, $items) as $t) $s .= '<span>' . e($t) . '</span>';
    return '<div class="strip" aria-hidden="true"><div class="track">' . $s . '</div></div>';
}

function trust_row(): string
{
    return '<div class="trust">'
        . '<div>' . ic('truck') . '<p><b>Nationwide delivery</b><span>Nsukka, Enugu State &amp; across Nigeria</span></p></div>'
        . '<div>' . ic('shield') . '<p><b>Secure checkout</b><span>Online, bank transfer or on delivery</span></p></div>'
        . '<div>' . ic('bucket') . '<p><b>Direct from the producer</b><span>Moj Paints made by Mojhometouch</span></p></div>'
        . '<div>' . ic('chat') . '<p><b>Real people, fast</b><span>Call or WhatsApp ' . e(setting('phone')) . '</span></p></div>'
        . '</div>';
}

function field_err(array $errors, string $k): string
{
    return isset($errors[$k]) ? '<span class="msg">' . e($errors[$k]) . '</span>' : '';
}
