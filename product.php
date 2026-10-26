<?php
require_once __DIR__ . '/includes/bootstrap.php';

$p = product_by_slug(qin('slug'));
if (!$p) not_found();
$vars = product_variants((int) $p['id']);
$p['_variants'] = $vars;
$pi = product_price_info($p);
$imgs = product_images($p);
$inStock = product_in_stock($p);
$onRequest = $pi['min'] <= 0;
$lowAt = (int) setting('low_stock', '5');

// default variant = first in stock
$defVar = null;
foreach ($vars as $v) if ($v['stock'] === null || (int) $v['stock'] > 0) { $defVar = $v; break; }
$curPrice = $defVar ? (int) $defVar['price'] : (int) $p['price'];
$curStock = $vars ? ($defVar ? $defVar['stock'] : 0) : $p['stock'];

$related = with_variants(q_all('SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE p.active = 1 AND p.id <> ? AND (p.category_id = ? OR p.featured = 1) ORDER BY CASE WHEN p.category_id = ? THEN 0 ELSE 1 END, p.sort LIMIT 4', [$p['id'], (int) $p['category_id'], (int) $p['category_id']]));
$zones = delivery_zones();
$waMsg = 'Hello Mojhometouch, I\'m interested in ' . $p['name'] . ' — ' . abs_url('product.php?slug=' . $p['slug']);

$title = $p['name'] . ' — ' . setting('business_name');
$desc = $p['short_desc'] ?: excerpt($p['description'], 155);
$og_image = $p['image'] ?: 'assets/img/og-image.jpg';
$og_type = 'product';
$active = 'shop';
$canonical_query = 'slug=' . rawurlencode($p['slug']);
$offer = $onRequest ? null : ['@type' => 'AggregateOffer', 'priceCurrency' => 'NGN', 'lowPrice' => $pi['min'], 'highPrice' => $pi['max'], 'availability' => $inStock ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock'];
$schema = array_filter(['@context' => 'https://schema.org', '@type' => 'Product', 'name' => $p['name'], 'description' => $desc, 'image' => abs_url($p['image'] ?: 'assets/img/og-image.jpg'), 'brand' => ['@type' => 'Brand', 'name' => 'Mojhometouch'], 'offers' => $offer]);
require __DIR__ . '/includes/layout/header.php';
?>
<section class="section" style="padding-top:40px"><div class="wrap">
  <div class="crumbs light"><a href="<?= e(url()) ?>">Home</a><span>/</span><a href="<?= e(url('shop.php')) ?>">Shop</a><?php if ($p['category_name']): ?><span>/</span><a href="<?= e(url('shop.php?cat=' . rawurlencode($p['category_slug']))) ?>"><?= e($p['category_name']) ?></a><?php endif; ?><span>/</span><span><?= e($p['name']) ?></span></div>
  <div class="pd" style="margin-top:20px">
    <div class="pd-gallery">
      <div class="pd-main"><img id="pdMain" src="<?= e(img_url($imgs[0])) ?>" alt="<?= e($p['name']) ?>"></div>
      <?php if (count($imgs) > 1): ?>
      <div class="pd-thumbs">
        <?php foreach ($imgs as $i => $im): ?><button type="button" class="<?= $i === 0 ? 'on' : '' ?>" data-src="<?= e(img_url($im)) ?>" aria-label="View image <?= $i + 1 ?>"><img src="<?= e(img_url($im)) ?>" alt="" loading="lazy"></button><?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <div class="pd-info">
      <?php if ($p['category_name']): ?><span class="eyebrow"><?= e($p['category_name']) ?></span><?php endif; ?>
      <h1><?= e($p['name']) ?></h1>
      <?php if ($onRequest): ?>
        <div class="pd-price" style="color:var(--gold-d)">Price on request</div>
      <?php else: ?>
        <div class="pd-price"><span id="pdPrice"><?= money($curPrice) ?></span>
          <?php if (!$vars && $p['compare_price'] && (int) $p['compare_price'] > $curPrice): ?><s><?= money($p['compare_price']) ?></s><?php endif; ?>
          <?php if ($p['unit']): ?><small><?= e($p['unit']) ?></small><?php endif; ?></div>
      <?php endif; ?>
      <p class="pd-short"><?= e($p['short_desc']) ?></p>

      <?php if ($onRequest): ?>
        <div class="buy-row">
          <a class="btn btn-wa btn-lg" href="<?= e(wa_link($waMsg . ' — please share the price.')) ?>" target="_blank" rel="noopener"><?= wa_icon() ?> Ask for Price</a>
          <a class="btn btn-line btn-lg" href="<?= e(url('contact.php?service=' . rawurlencode('Interior Décor & Styling') . '#quote')) ?>">Request a Quote</a>
        </div>
      <?php else: ?>
      <form method="post" action="<?= e(url('cart.php')) ?>" class="js-add" id="buyForm">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="add">
        <input type="hidden" name="product_id" value="<?= (int) $p['id'] ?>">
        <?php if ($vars): ?>
        <div class="opt-group">
          <span class="opt-title">Size / Option</span>
          <div class="pills" role="radiogroup">
            <?php foreach ($vars as $v): $out = $v['stock'] !== null && (int) $v['stock'] <= 0; ?>
              <input type="radio" name="variant_id" id="v<?= (int) $v['id'] ?>" value="<?= (int) $v['id'] ?>" data-price="<?= money($v['price']) ?>" data-stock="<?= $v['stock'] === null ? '' : (int) $v['stock'] ?>"<?= $defVar && $defVar['id'] === $v['id'] ? ' checked' : '' ?><?= $out ? ' disabled' : '' ?>>
              <label for="v<?= (int) $v['id'] ?>"><?= e($v['name']) ?><small><?= $out ? 'Sold out' : money($v['price']) ?></small></label>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>
        <?php if ($p['option_label']): ?>
        <div class="opt-group">
          <label for="opt"><?= e($p['option_label']) ?> <span class="muted" style="text-transform:none;letter-spacing:0;font-weight:600">(optional)</span></label>
          <input class="input" id="opt" name="option" maxlength="120" placeholder="<?= e(stripos($p['option_label'], 'colour') !== false ? 'e.g. Off-white, Ash grey, Cream — leave blank and we\'ll call to advise' : 'Tell us your preference') ?>">
        </div>
        <?php endif; ?>
        <div class="buy-row">
          <div class="qty" data-qty><button type="button" data-step="-1" aria-label="Decrease quantity"><?= ic('minus') ?></button><input type="number" name="qty" value="1" min="1" max="999" inputmode="numeric" aria-label="Quantity"><button type="button" data-step="1" aria-label="Increase quantity"><?= ic('plus') ?></button></div>
          <button class="btn btn-dark btn-lg" type="submit" <?= $inStock ? '' : 'disabled' ?>><?= ic('bag') ?> <?= $inStock ? 'Add to Cart' : 'Sold Out' ?></button>
          <button class="btn btn-primary btn-lg" type="submit" name="buy_now" value="1" <?= $inStock ? '' : 'disabled' ?>>Buy Now</button>
        </div>
        <?php
          $sn = ''; $cls = '';
          if (!$inStock) { $sn = 'Out of stock — ask us about restock dates'; $cls = 'out'; }
          elseif ($curStock !== null && (int) $curStock <= $lowAt) { $sn = 'Only ' . (int) $curStock . ' left in stock'; $cls = 'low'; }
          else { $sn = 'In stock — ready to deliver'; }
        ?>
        <p class="stock-note <?= $cls ?>" id="stockNote" data-low="<?= $lowAt ?>"><?= e($sn) ?></p>
      </form>
      <?php endif; ?>

      <div class="pd-perks">
        <div><?= ic('truck') ?><p><b>Delivery or pickup</b><span><?php
          $paid = array_filter($zones, fn($z) => !$z['is_pickup']);
          echo $paid ? 'Delivery from ' . money(min(array_column($paid, 'fee'))) . ' · ' : '';
          echo array_filter($zones, fn($z) => $z['is_pickup']) ? 'Free pickup in Nsukka' : '';
        ?></span></p></div>
        <div><?= ic('shield') ?><p><b>Secure ordering</b><span>Pay online, by bank transfer or on delivery</span></p></div>
        <div><?= wa_icon() ?><p><b>Questions?</b><span><a class="link" href="<?= e(wa_link($waMsg)) ?>" target="_blank" rel="noopener">Chat with us on WhatsApp</a></span></p></div>
      </div>
    </div>
  </div>

  <?php if (trim(strip_tags((string) $p['description'])) !== ''): ?>
  <div class="pd-desc rv"><h2 style="margin-bottom:16px">Product details</h2><div class="prose"><?= $p['description'] ?></div></div>
  <?php endif; ?>
</div></section>

<?php if ($related): ?>
<section class="section sand"><div class="wrap">
  <div class="sec-head"><div><span class="eyebrow">You may also like</span><h2>Complete the look</h2></div><a class="btn btn-dark" href="<?= e(url('shop.php')) ?>">All Products <?= ic('arrow') ?></a></div>
  <div class="pgrid"><?php foreach ($related as $r) echo product_card($r); ?></div>
</div></section>
<?php endif; ?>
<?php require __DIR__ . '/includes/layout/footer.php';
