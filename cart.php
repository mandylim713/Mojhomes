<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (is_post()) {
    csrf_check();
    $action = pin('action');
    if ($action === 'add') {
        [$ok, $msg] = cart_add((int) pin('product_id'), (int) pin('variant_id', '0'), pin('option'), (int) pin('qty', '1'));
        if (is_ajax()) json_out(['ok' => $ok, 'message' => $msg, 'count' => cart_count(), 'cart_url' => url('cart.php'), 'checkout_url' => url('checkout.php')], $ok ? 200 : 422);
        flash($ok ? 'success' : 'error', $msg);
        if ($ok && pin('buy_now') === '1') redirect(url('checkout.php'));
        if ($ok) redirect(url('cart.php'));
        back(url('shop.php'));
    }
    if ($action === 'update') {
        $msg = cart_set_qty(pin('key'), (int) pin('qty', '1'));
        if ($msg) flash('error', $msg);
        redirect(url('cart.php'));
    }
    if ($action === 'remove') {
        cart_remove(pin('key'));
        flash('success', 'Item removed from your cart.');
        redirect(url('cart.php'));
    }
    if ($action === 'clear') {
        cart_clear();
        redirect(url('cart.php'));
    }
    redirect(url('cart.php'));
}

$cart = cart_lines();
foreach ($cart['issues'] as $iss) flash('error', $iss);
$title = 'Your Cart — ' . setting('business_name');
$robots = 'noindex';
$active = 'shop';
require __DIR__ . '/includes/layout/header.php';
?>
<section class="section" style="padding-top:44px"><div class="wrap">
  <div class="crumbs light"><a href="<?= e(url()) ?>">Home</a><span>/</span><a href="<?= e(url('shop.php')) ?>">Shop</a><span>/</span><span>Cart</span></div>
  <h1 style="font-size:clamp(2.2rem,4vw,3.2rem);margin:10px 0 30px">Your Cart</h1>
  <?php if (!$cart['lines']): ?>
    <div class="empty"><?= ic('bag') ?><h3>Your cart is empty</h3><p>Browse Moj Paints, wall panels and décor — and add your favourites.</p><a class="btn btn-dark btn-lg" href="<?= e(url('shop.php')) ?>">Start Shopping <?= ic('arrow') ?></a></div>
  <?php else: ?>
  <div class="cart-grid">
    <div>
      <div class="cart-list">
        <?php foreach ($cart['lines'] as $l): $href = url('product.php?slug=' . rawurlencode($l['slug'])); ?>
        <div class="cart-row">
          <a href="<?= e($href) ?>"><img src="<?= e(img_url($l['image'])) ?>" alt="<?= e($l['name']) ?>" loading="lazy"></a>
          <div class="info">
            <h3><a href="<?= e($href) ?>"><?= e($l['name']) ?></a></h3>
            <div class="meta"><?= e(implode(' · ', array_filter([$l['variant_name'], $l['option'] !== '' ? ($l['option_label'] ?: 'Option') . ': ' . $l['option'] : null]))) ?></div>
            <div class="meta"><?= money($l['unit_price']) ?> <?= $l['unit'] ? e($l['unit']) : 'each' ?></div>
          </div>
          <form method="post" class="qty sm js-qty" data-qty>
            <?= csrf_field() ?><input type="hidden" name="action" value="update"><input type="hidden" name="key" value="<?= e($l['key']) ?>">
            <button type="button" data-step="-1" aria-label="Decrease"><?= ic('minus') ?></button>
            <input type="number" name="qty" value="<?= (int) $l['qty'] ?>" min="0" max="<?= $l['stock'] ?? 999 ?>" aria-label="Quantity for <?= e($l['name']) ?>">
            <button type="button" data-step="1" aria-label="Increase"><?= ic('plus') ?></button>
          </form>
          <div class="lt"><?= money($l['line_total']) ?></div>
          <form method="post" class="rm"><?= csrf_field() ?><input type="hidden" name="action" value="remove"><input type="hidden" name="key" value="<?= e($l['key']) ?>"><button class="icon-btn" type="submit" aria-label="Remove <?= e($l['name']) ?>"><?= ic('trash') ?></button></form>
        </div>
        <?php endforeach; ?>
      </div>
      <div style="display:flex;justify-content:space-between;gap:12px;margin-top:18px;flex-wrap:wrap">
        <a class="link" href="<?= e(url('shop.php')) ?>"><?= ic('back') ?> Continue shopping</a>
        <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="clear"><button class="link" style="background:none;border:0;color:var(--muted)" type="submit">Clear cart</button></form>
      </div>
    </div>
    <aside class="summary">
      <h3>Order Summary</h3>
      <div class="sum-row"><span>Subtotal (<?= (int) $cart['count'] ?> item<?= $cart['count'] == 1 ? '' : 's' ?>)</span><b><?= money($cart['subtotal']) ?></b></div>
      <div class="sum-row"><span>Delivery</span><span class="muted">Calculated at checkout</span></div>
      <div class="sum-row total"><span>Total</span><span><?= money($cart['subtotal']) ?></span></div>
      <a class="btn btn-primary btn-lg btn-block" style="margin-top:18px" href="<?= e(url('checkout.php')) ?>">Proceed to Checkout <?= ic('arrow') ?></a>
      <a class="btn btn-wa btn-block" style="margin-top:10px" href="<?= e(wa_link("Hello Mojhometouch, I'd like to order:\n" . implode("\n", array_map(fn($l) => '• ' . $l['qty'] . ' × ' . $l['name'] . ($l['variant_name'] ? ' (' . $l['variant_name'] . ')' : '') . ($l['option'] !== '' ? ' — ' . $l['option'] : ''), $cart['lines'])))) ?>" target="_blank" rel="noopener"><?= wa_icon() ?> Order via WhatsApp</a>
      <p class="secure"><?= ic('lock') ?> Secure checkout · Track your order online</p>
    </aside>
  </div>
  <?php endif; ?>
</div></section>
<?php require __DIR__ . '/includes/layout/footer.php';
