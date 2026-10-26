<?php
require_once __DIR__ . '/includes/bootstrap.php';

$o = order_by_ref(qin('ref'));
if (!$o || !hash_equals($o['token'], qin('t'))) not_found();

if (is_post()) {
    csrf_check();
    if (pin('action') === 'proof' && $o['payment_method'] === 'bank' && in_array($o['payment_status'], ['unpaid', 'failed', 'review'], true) && $o['status'] !== 'cancelled') {
        try {
            if (!has_upload('proof')) throw new RuntimeException('Please choose your payment receipt to upload.');
            if (throttled('proof', 6, 900)) throw new RuntimeException('Too many uploads. Please wait a few minutes.');
            $file = upload_proof($_FILES['proof']);
            if ($o['proof_file'] && is_file(ROOT . '/storage/proofs/' . basename($o['proof_file']))) @unlink(ROOT . '/storage/proofs/' . basename($o['proof_file']));
            q('UPDATE orders SET proof_file = ?, updated_at = ? WHERE id = ?', [$file, now(), $o['id']]);
            $note = pin('payer') !== '' ? 'Payment receipt uploaded (payer: ' . mb_substr(pin('payer'), 0, 120) . ').' : 'Payment receipt uploaded.';
            order_set_payment($o, 'review', 'Customer', $note);
            $admin = setting('notify_email') ?: setting('email');
            if ($admin) send_mail($admin, 'Payment receipt uploaded — ' . $o['ref'], '<p>' . e($o['customer_name']) . ' uploaded a payment receipt for order <strong>' . e($o['ref']) . '</strong> (' . money($o['total']) . ').</p>' . mail_button(abs_url('admin/order.php?id=' . $o['id']), 'Review payment'));
            flash('success', 'Thank you! Your receipt was uploaded. We will confirm your payment shortly.');
        } catch (RuntimeException $ex) {
            flash('error', $ex->getMessage());
        }
    }
    redirect(order_url($o));
}

$items = order_items((int) $o['id']);
$events = order_events((int) $o['id'], true);
$isNew = qin('new') === '1';
$cancelled = $o['status'] === 'cancelled';
$waMsg = 'Hello Mojhometouch, about my order ' . $o['ref'] . ' (' . money($o['total']) . ').';

$heads = [
    'pending' => ['Order received', 'Thank you, ' . $o['customer_name'] . '! We have received your order.'],
    'confirmed' => ['Order confirmed', 'Your order is confirmed and will be prepared shortly.'],
    'processing' => ['Being prepared', 'We are preparing your order.'],
    'ready' => ['Ready for pickup', 'Your order is ready at our Nsukka showroom. Bring your order number.'],
    'shipped' => ['On its way', 'Your order is out for delivery. Please keep your phone close.'],
    'delivered' => ['Delivered', 'Your order is complete. Thank you for choosing Mojhometouch!'],
    'cancelled' => ['Order cancelled', 'This order has been cancelled. Contact us if you have any questions.'],
];
[$h1, $sub] = $heads[$o['status']] ?? $heads['pending'];

$title = 'Order ' . $o['ref'] . ' — ' . setting('business_name');
$robots = 'noindex, nofollow';
require __DIR__ . '/includes/layout/header.php';
?>
<section class="section" style="padding-top:40px"><div class="wrap">
  <div class="print-head"><img src="<?= e(url('assets/img/logo.png')) ?>" alt=""><div style="text-align:right"><b><?= e(setting('business_name')) ?></b><br><?= e(setting('address')) ?><br><?= e(setting('phone')) ?></div></div>

  <div class="status-hero">
    <?php if ($isNew && !$cancelled): ?><div class="ok-ring"><?= ic('tick') ?></div><?php endif; ?>
    <h1><?= e($h1) ?></h1>
    <p><?= e($sub) ?></p>
    <div class="ref">Order <span><?= e($o['ref']) ?></span> · <?= fdate($o['created_at'], 'j M Y') ?></div>
    <?php if ($cancelled): ?>
      <div class="cancel-note">This order was cancelled.</div>
    <?php else: ?>
      <div class="tracker" aria-label="Order progress">
        <?php foreach (order_steps($o) as $s): ?><div class="<?= $s['done'] ? 'done' : '' ?><?= $s['current'] ? ' current' : '' ?>"><?= e($s['label']) ?></div><?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <?php if ($isNew): ?><div class="alert alert-info no-print"><?= ic('alert') ?><div><b>Save this page.</b> Bookmark it or note your order number <b><?= e($o['ref']) ?></b> — you can also track it anytime on the <a href="<?= e(url('track.php')) ?>" style="text-decoration:underline">Track Order</a> page with your phone number.</div></div><?php endif; ?>

  <div class="order-grid">
    <div>
      <?php if (!$cancelled && $o['payment_status'] !== 'paid' && $o['payment_status'] !== 'refunded'): ?>
      <div class="panel pay-panel no-print">
        <?php if ($o['payment_method'] === 'flutterwave'): ?>
          <h2><?= ic('card') ?> Complete your payment</h2>
          <p class="muted">Pay <b><?= money($o['total']) ?></b> securely by card, bank transfer or USSD via Flutterwave.</p>
          <?php if (flw_enabled()): ?>
            <a class="btn btn-primary btn-lg" style="margin-top:16px" href="<?= e(url('pay/start.php?ref=' . rawurlencode($o['ref']) . '&t=' . $o['token'])) ?>"><?= ic('lock') ?> Pay <?= money($o['total']) ?> Now</a>
          <?php else: ?>
            <p style="margin-top:12px">Online payment is temporarily unavailable. Please <a class="link" href="<?= e(wa_link($waMsg . ' I would like to pay.')) ?>" target="_blank" rel="noopener">contact us on WhatsApp</a> to complete your payment.</p>
          <?php endif; ?>
        <?php elseif ($o['payment_method'] === 'bank'): ?>
          <h2><?= ic('bank') ?> Pay by bank transfer</h2>
          <?php if ($o['payment_status'] === 'review'): ?>
            <div class="alert alert-info"><?= ic('clock') ?><div>We've received your receipt and are confirming your payment. You'll see the status update here.</div></div>
          <?php endif; ?>
          <p class="muted">Transfer <b><?= money($o['total']) ?></b> to the account below. Use <b><?= e($o['ref']) ?></b> as the narration/description.</p>
          <div class="bank-box">
            <div><span>Bank</span><b><?= e(setting('bank_name', '—')) ?></b></div>
            <div><span>Account name</span><b><?= e(setting('bank_account_name')) ?></b></div>
            <div><span>Account number</span><b><span data-copy-src><?= e(setting('bank_account_number')) ?></span><button type="button" class="copy" data-copy="<?= e(setting('bank_account_number')) ?>">COPY</button></b></div>
            <div><span>Amount</span><b><?= money($o['total']) ?><button type="button" class="copy" data-copy="<?= (int) $o['total'] ?>">COPY</button></b></div>
            <div><span>Narration</span><b><?= e($o['ref']) ?><button type="button" class="copy" data-copy="<?= e($o['ref']) ?>">COPY</button></b></div>
          </div>
          <form method="post" enctype="multipart/form-data" class="fields">
            <?= csrf_field() ?><input type="hidden" name="action" value="proof">
            <label class="file-drop"><?= ic('upload') ?><b><?= $o['proof_file'] ? 'Upload a different receipt' : 'Upload payment receipt' ?></b><span class="fn">Screenshot, photo or PDF · max 8MB</span><input type="file" name="proof" accept="image/jpeg,image/png,image/webp,application/pdf" required></label>
            <label class="field"><span>Name on the paying account <span class="hint">(optional)</span></span><input name="payer" maxlength="120"></label>
            <button class="btn btn-dark" type="submit"><?= ic('upload') ?> Submit Receipt</button>
          </form>
        <?php else: ?>
          <h2><?= ic('cash') ?> Pay on <?= $o['is_pickup'] ? 'pickup' : 'delivery' ?></h2>
          <p class="muted">Please have <b><?= money($o['total']) ?></b> ready when your order is <?= $o['is_pickup'] ? 'collected' : 'delivered' ?>. Cash, transfer or POS accepted. Our team will call <?= e($o['phone']) ?> to confirm.</p>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <div class="panel">
        <h2><?= ic('box') ?> Items</h2>
        <table class="items-tbl">
          <thead><tr><th>Item</th><th class="c">Qty</th><th class="r">Total</th></tr></thead>
          <tbody>
          <?php foreach ($items as $it): ?>
            <tr><td><div class="it"><img src="<?= e(img_url($it['image'])) ?>" alt="" loading="lazy"><div><b><?= e($it['name']) ?></b><small><?= e(implode(' · ', array_filter([$it['variant_name'], $it['option_value'] ? ($it['option_label'] ?: 'Option') . ': ' . $it['option_value'] : null, money($it['unit_price']) . ' each']))) ?></small></div></div></td>
            <td class="c"><?= (int) $it['qty'] ?></td><td class="r"><b><?= money($it['line_total']) ?></b></td></tr>
          <?php endforeach; ?>
          </tbody>
        </table>
        <div class="kv" style="margin-top:16px;max-width:360px;margin-left:auto">
          <div><span>Subtotal</span><b><?= money($o['subtotal']) ?></b></div>
          <div><span>Delivery</span><b><?= (int) $o['delivery_fee'] ? money($o['delivery_fee']) : 'Free' ?></b></div>
          <div style="font-size:1.15rem;border-top:1px solid var(--line);padding-top:10px"><span style="color:var(--ink);font-weight:800">Total</span><b><?= money($o['total']) ?></b></div>
        </div>
      </div>

      <div class="panel no-print">
        <h2><?= ic('clock') ?> Order updates</h2>
        <ul class="timeline">
          <?php foreach (array_reverse($events) as $ev): ?>
            <li><b><?= e($ev['note']) ?></b><small><?= fdate($ev['created_at']) ?><?= $ev['status'] ? ' · ' . e(ORDER_STATUSES[$ev['status']] ?? '') : '' ?></small></li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>

    <aside>
      <div class="panel">
        <h2>Summary</h2>
        <div class="kv">
          <div><span>Order</span><b><?= e($o['ref']) ?></b></div>
          <div><span>Date</span><b><?= fdate($o['created_at']) ?></b></div>
          <div><span>Status</span><b><?= badge_status($o['status']) ?></b></div>
          <div><span>Payment</span><b><?= badge_pay($o['payment_status']) ?></b></div>
          <div><span>Method</span><b><?= e(payment_method_label($o['payment_method'])) ?></b></div>
          <div><span>Total</span><b><?= money($o['total']) ?></b></div>
        </div>
      </div>
      <div class="panel">
        <h2><?= $o['is_pickup'] ? 'Pickup' : 'Delivery' ?></h2>
        <div class="kv">
          <div><span>Name</span><b><?= e($o['customer_name']) ?></b></div>
          <div><span>Phone</span><b><?= e($o['phone']) ?></b></div>
          <?php if ($o['email']): ?><div><span>Email</span><b style="word-break:break-all"><?= e($o['email']) ?></b></div><?php endif; ?>
          <div><span>Option</span><b><?= e($o['zone_name']) ?></b></div>
          <?php if (!$o['is_pickup']): ?><div><span>Address</span><b><?= nl2br(e($o['address'])) ?><br><?= e($o['city']) ?>, <?= e($o['state']) ?></b></div><?php endif; ?>
          <?php if ($o['notes']): ?><div><span>Notes</span><b><?= nl2br(e($o['notes'])) ?></b></div><?php endif; ?>
        </div>
      </div>
      <div class="no-print" style="display:grid;gap:10px">
        <a class="btn btn-wa btn-block" href="<?= e(wa_link($waMsg)) ?>" target="_blank" rel="noopener"><?= wa_icon() ?> Message us about this order</a>
        <button class="btn btn-line btn-block" type="button" onclick="window.print()"><?= ic('print') ?> Print / Save receipt</button>
        <a class="btn btn-dark btn-block" href="<?= e(url('shop.php')) ?>">Continue Shopping</a>
      </div>
    </aside>
  </div>
</div></section>
<?php require __DIR__ . '/includes/layout/footer.php';
