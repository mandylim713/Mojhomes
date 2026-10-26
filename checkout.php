<?php
require_once __DIR__ . '/includes/bootstrap.php';

$cart = cart_lines();
if (!$cart['lines']) {
    flash('error', 'Your cart is empty.');
    redirect(url('cart.php'));
}
$zones = delivery_zones();
$methods = payment_methods_available();
$errors = [];
$d = $_SESSION['checkout'] ?? [];
$d += ['name' => '', 'phone' => '', 'email' => '', 'address' => '', 'city' => '', 'state' => 'Enugu', 'zone' => '', 'payment' => '', 'notes' => ''];

if (is_post()) {
    csrf_check();
    foreach (['name', 'phone', 'email', 'address', 'city', 'state', 'zone', 'payment', 'notes'] as $k) $d[$k] = mb_substr(pin($k), 0, $k === 'notes' || $k === 'address' ? 1000 : 190);
    $zone = null;
    foreach ($zones as $z) if ((string) $z['id'] === $d['zone']) $zone = $z;

    if ($d['name'] === '') $errors['name'] = 'Please enter your full name.';
    if (!valid_phone($d['phone'])) $errors['phone'] = 'Please enter a valid phone number.';
    if ($d['email'] !== '' && !valid_email($d['email'])) $errors['email'] = 'Please enter a valid email address.';
    if (!$zone) $errors['zone'] = 'Please choose delivery or pickup.';
    if ($zone && !$zone['is_pickup']) {
        if ($d['address'] === '') $errors['address'] = 'Please enter your delivery address.';
        if ($d['city'] === '') $errors['city'] = 'Please enter your town / city.';
        if (!in_array($d['state'], NG_STATES, true)) $errors['state'] = 'Please choose your state.';
    }
    if (!isset($methods[$d['payment']])) $errors['payment'] = 'Please choose a payment method.';
    if ($d['payment'] === 'flutterwave' && !valid_email($d['email'])) $errors['email'] = 'An email address is required for online payment (for your receipt).';
    if (pin('agree') !== '1') $errors['agree'] = 'Please accept the terms to continue.';
    if (!$errors && honeypot_tripped()) $errors['form'] = 'Something went wrong. Please try again.';
    if (!$errors && throttled('checkout', 6, 600)) $errors['form'] = 'Too many orders in a short time. Please wait a few minutes or call us.';

    $_SESSION['checkout'] = array_diff_key($d, ['notes' => 1]);

    if (!$errors) {
        $fresh = cart_lines();
        if ($fresh['issues'] || $fresh['subtotal'] !== $cart['subtotal']) {
            foreach ($fresh['issues'] as $iss) flash('error', $iss);
            if (!$fresh['issues']) flash('error', 'Prices in your cart were updated. Please review your order.');
            redirect(url('checkout.php'));
        }
        try {
            $order = order_create([
                'name' => $d['name'], 'phone' => $d['phone'], 'email' => $d['email'],
                'address' => $zone['is_pickup'] ? '' : $d['address'], 'city' => $zone['is_pickup'] ? '' : $d['city'],
                'state' => $zone['is_pickup'] ? '' : $d['state'], 'notes' => $d['notes'],
            ], $fresh, $zone, $d['payment']);
        } catch (RuntimeException $ex) {
            flash('error', $ex->getMessage());
            redirect(url('cart.php'));
        }
        cart_clear();
        $_SESSION['my_orders'][] = $order['ref'];
        try { notify_new_order($order); } catch (Throwable $ex) { error_log((string) $ex); }

        if ($order['payment_method'] === 'flutterwave') {
            try {
                redirect(flw_checkout_link($order));
            } catch (RuntimeException $ex) {
                flash('error', $ex->getMessage());
            }
        }
        redirect(order_url($order) . '&new=1');
    }
}

if (!$d['zone'] && $zones) $d['zone'] = (string) $zones[0]['id'];
if (!$d['payment'] && $methods) $d['payment'] = array_key_first($methods);
$selZone = null;
foreach ($zones as $z) if ((string) $z['id'] === $d['zone']) $selZone = $z;

$title = 'Checkout — ' . setting('business_name');
$robots = 'noindex';
$active = 'shop';
require __DIR__ . '/includes/layout/header.php';
$cls = fn($k) => 'field' . (isset($errors[$k]) ? ' err' : '');
?>
<section class="section" style="padding-top:44px"><div class="wrap">
  <div class="crumbs light"><a href="<?= e(url('cart.php')) ?>">Cart</a><span>/</span><span>Checkout</span></div>
  <h1 style="font-size:clamp(2.2rem,4vw,3.2rem);margin:10px 0 30px">Checkout</h1>
  <?php if ($errors): ?><div class="alert alert-error"><?= ic('alert') ?><div><?= isset($errors['form']) ? e($errors['form']) : 'Please check the highlighted fields below.' ?></div></div><?php endif; ?>
  <?php if (!$methods): ?><div class="alert alert-warn"><?= ic('alert') ?><div>Online checkout is not available right now. Please <a href="<?= e(wa_link()) ?>" style="text-decoration:underline">order on WhatsApp</a> or call <?= e(setting('phone')) ?>.</div></div><?php endif; ?>

  <form method="post" class="co-grid" id="checkout" novalidate>
    <?= csrf_field() ?><?= honeypot_field() ?>
    <div>
      <div class="co-card">
        <h2><span class="n">1</span> Your details</h2>
        <div class="fields">
          <label class="<?= $cls('name') ?>"><span>Full name <em>*</em></span><input name="name" value="<?= e($d['name']) ?>" autocomplete="name" required><?= field_err($errors, 'name') ?></label>
          <div class="two">
            <label class="<?= $cls('phone') ?>"><span>Phone / WhatsApp <em>*</em></span><input name="phone" type="tel" value="<?= e($d['phone']) ?>" autocomplete="tel" required placeholder="080…"><?= field_err($errors, 'phone') ?></label>
            <label class="<?= $cls('email') ?>"><span>Email <span class="hint" id="emailHint">(for your receipt)</span></span><input name="email" type="email" value="<?= e($d['email']) ?>" autocomplete="email"><?= field_err($errors, 'email') ?></label>
          </div>
        </div>
      </div>

      <div class="co-card">
        <h2><span class="n">2</span> Delivery or pickup</h2>
        <div class="opts">
          <?php foreach ($zones as $z): ?>
          <div class="opt">
            <input type="radio" name="zone" id="z<?= (int) $z['id'] ?>" value="<?= (int) $z['id'] ?>" data-fee="<?= (int) $z['fee'] ?>" data-pickup="<?= (int) $z['is_pickup'] ?>"<?= (string) $z['id'] === $d['zone'] ? ' checked' : '' ?>>
            <label for="z<?= (int) $z['id'] ?>"><span class="dot"></span><span class="ot"><b><?= e($z['name']) ?></b><small><?= e($z['eta']) ?></small></span><span class="ov"><?= (int) $z['fee'] ? money($z['fee']) : 'Free' ?></span></label>
          </div>
          <?php endforeach; ?>
        </div>
        <?= field_err($errors, 'zone') ?>
        <div class="fields" id="addrFields" style="margin-top:20px<?= $selZone && $selZone['is_pickup'] ? ';display:none' : '' ?>">
          <label class="<?= $cls('address') ?>"><span>Delivery address <em>*</em></span><textarea name="address" rows="2" style="min-height:80px" autocomplete="street-address" placeholder="House number, street, landmark"><?= e($d['address']) ?></textarea><?= field_err($errors, 'address') ?></label>
          <div class="two">
            <label class="<?= $cls('city') ?>"><span>Town / City <em>*</em></span><input name="city" value="<?= e($d['city']) ?>" autocomplete="address-level2"><?= field_err($errors, 'city') ?></label>
            <label class="<?= $cls('state') ?>"><span>State <em>*</em></span><select name="state" autocomplete="address-level1"><?php foreach (NG_STATES as $s): ?><option<?= $d['state'] === $s ? ' selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?></select><?= field_err($errors, 'state') ?></label>
          </div>
        </div>
        <p class="muted" id="pickupNote" style="margin-top:16px;font-size:.9rem<?= $selZone && $selZone['is_pickup'] ? '' : ';display:none' ?>"><?= ic('pin') ?> Pickup from our showroom in <?= e(setting('address')) ?>. We'll call you when your order is ready.</p>
      </div>

      <div class="co-card">
        <h2><span class="n">3</span> Payment</h2>
        <div class="opts">
          <?php foreach ($methods as $k => [$label, $sub, $icon]): ?>
          <div class="opt">
            <input type="radio" name="payment" id="pm-<?= e($k) ?>" value="<?= e($k) ?>"<?= $d['payment'] === $k ? ' checked' : '' ?>>
            <label for="pm-<?= e($k) ?>"><span class="dot"></span><span class="ot"><b><?= e($label) ?></b><small><?= e($sub) ?></small></span><?= ic($icon) ?></label>
          </div>
          <?php endforeach; ?>
        </div>
        <?= field_err($errors, 'payment') ?>
        <label class="field" style="margin-top:20px"><span>Order notes <span class="hint">(optional)</span></span><textarea name="notes" rows="3" style="min-height:90px" placeholder="Colour details, delivery instructions, best time to call…"><?= e($d['notes']) ?></textarea></label>
      </div>
    </div>

    <aside class="summary">
      <h3>Your Order</h3>
      <div class="sum-items">
        <?php foreach ($cart['lines'] as $l): ?>
        <div class="sum-item"><img src="<?= e(img_url($l['image'])) ?>" alt="" loading="lazy"><div><b><?= e($l['name']) ?></b><small><?= e(implode(' · ', array_filter([$l['variant_name'], $l['option'] ?: null, 'Qty ' . $l['qty']]))) ?></small></div><b><?= money($l['line_total']) ?></b></div>
        <?php endforeach; ?>
      </div>
      <div class="sum-row"><span>Subtotal</span><b data-subtotal="<?= (int) $cart['subtotal'] ?>"><?= money($cart['subtotal']) ?></b></div>
      <div class="sum-row"><span>Delivery</span><b id="feeOut"><?= $selZone ? ((int) $selZone['fee'] ? money($selZone['fee']) : 'Free') : '—' ?></b></div>
      <div class="sum-row total"><span>Total</span><span id="totalOut"><?= money($cart['subtotal'] + (int) ($selZone['fee'] ?? 0)) ?></span></div>
      <label class="check" style="margin:16px 0 4px"><input type="checkbox" name="agree" value="1"<?= is_post() && pin('agree') === '1' ? ' checked' : '' ?>><span>I agree to the <a href="<?= e(url('page.php?p=terms')) ?>" target="_blank">Terms</a> and <a href="<?= e(url('page.php?p=delivery-returns')) ?>" target="_blank">Delivery &amp; Returns</a> policy.</span></label>
      <?= field_err($errors, 'agree') ?>
      <button class="btn btn-primary btn-lg btn-block" style="margin-top:16px" type="submit" <?= $methods ? '' : 'disabled' ?> id="placeBtn"><?= ic('lock') ?> Place Order</button>
      <p class="secure"><?= ic('shield') ?> Your details are kept private and secure.</p>
      <a class="link" style="margin-top:14px;justify-content:center;width:100%" href="<?= e(url('cart.php')) ?>"><?= ic('back') ?> Back to cart</a>
    </aside>
  </form>
</div></section>
<?php require __DIR__ . '/includes/layout/footer.php';
