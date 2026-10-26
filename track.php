<?php
require_once __DIR__ . '/includes/bootstrap.php';

$err = '';
$ref = strtoupper(qin('ref'));
$phone = '';
if (is_post()) {
    csrf_check();
    $ref = strtoupper(preg_replace('~\s+~', '', pin('ref')));
    $phone = pin('phone');
    if (throttled('track', 10, 600)) {
        $err = 'Too many attempts. Please wait a few minutes and try again.';
    } else {
        $o = $ref !== '' ? order_by_ref($ref) : null;
        if ($o && phone_match($o['phone'], $phone)) redirect(order_url($o));
        $err = 'We couldn\'t find an order with that number and phone. Please check and try again.';
    }
}
$recent = [];
foreach (array_reverse(array_slice($_SESSION['my_orders'] ?? [], -5)) as $r) {
    if ($x = order_by_ref($r)) $recent[] = $x;
}
$title = 'Track Your Order — ' . setting('business_name');
$desc = 'Track your Mojhometouch order with your order number and phone number.';
require __DIR__ . '/includes/layout/header.php';
echo page_hero('<span>Track Order</span>', 'Track your order', 'Enter your order number and the phone number used at checkout.', 'assets/img/door-finishing-wood.jpg', true);
?>
<section class="section"><div class="wrap" style="max-width:760px">
  <form method="post" class="form rv" novalidate>
    <?= csrf_field() ?>
    <?php if ($err): ?><div class="alert alert-error"><?= ic('alert') ?><div><?= e($err) ?></div></div><?php endif; ?>
    <div class="fields">
      <div class="two">
        <label class="field"><span>Order number</span><input name="ref" value="<?= e($ref) ?>" placeholder="e.g. MOJ261025-AB12" required style="text-transform:uppercase"></label>
        <label class="field"><span>Phone number</span><input name="phone" type="tel" value="<?= e($phone) ?>" placeholder="080…" required></label>
      </div>
      <button class="btn btn-dark btn-lg" type="submit"><?= ic('search') ?> Track Order</button>
    </div>
  </form>
  <?php if ($recent): ?>
  <div class="panel" style="margin-top:28px">
    <h2>Your recent orders</h2>
    <div class="kv">
      <?php foreach ($recent as $r): ?><div><a class="link" href="<?= e(order_url($r)) ?>"><?= e($r['ref']) ?></a><b><?= badge_status($r['status']) ?> <?= money($r['total']) ?></b></div><?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>
  <p class="muted center" style="margin-top:28px">Can't find your order number? <a class="link" href="<?= e(wa_link('Hello Mojhometouch, I need help finding my order.')) ?>" target="_blank" rel="noopener">Message us on WhatsApp</a></p>
</div></section>
<?php require __DIR__ . '/includes/layout/footer.php';
