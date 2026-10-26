<?php
require_once __DIR__ . '/_init.php';
$me = require_admin();

$o = order_get((int) qin('id'));
if (!$o) { flash('error', 'Order not found.'); redirect(url('admin/orders.php')); }
$self = url('admin/order.php?id=' . $o['id']);

if (is_post()) {
    csrf_check();
    $act = pin('action');
    try {
        if ($act === 'status') {
            order_set_status($o, pin('status'), pin('note'), $me['name'], pin('notify') === '1');
            flash('success', 'Order status updated to ' . (ORDER_STATUSES[pin('status')] ?? '') . '.');
        } elseif ($act === 'payment') {
            $ps = pin('payment_status');
            order_set_payment($o, $ps, $me['name'], pin('note'), pin('pay_ref') !== '' ? mb_substr(pin('pay_ref'), 0, 120) : null);
            flash('success', 'Payment marked as ' . (PAYMENT_STATUSES[$ps] ?? $ps) . '.');
        } elseif ($act === 'notes') {
            q('UPDATE orders SET admin_notes = ?, updated_at = ? WHERE id = ?', [mb_substr(pin('admin_notes'), 0, 5000), now(), $o['id']]);
            flash('success', 'Internal notes saved.');
        } elseif ($act === 'message') {
            if (pin('note') === '') throw new RuntimeException('Please write a message.');
            order_event((int) $o['id'], null, mb_substr(pin('note'), 0, 1000), $me['name'], pin('visible') === '1');
            if (pin('visible') === '1' && pin('notify') === '1') notify_status($o, pin('note'));
            flash('success', 'Update added to the order timeline.');
        } elseif ($act === 'delete') {
            if ($o['status'] !== 'cancelled') throw new RuntimeException('Only cancelled orders can be deleted.');
            if ($o['proof_file']) @unlink(ROOT . '/storage/proofs/' . basename($o['proof_file']));
            q('DELETE FROM order_items WHERE order_id = ?', [$o['id']]);
            q('DELETE FROM order_events WHERE order_id = ?', [$o['id']]);
            q('DELETE FROM orders WHERE id = ?', [$o['id']]);
            flash('success', 'Order ' . $o['ref'] . ' deleted.');
            redirect(url('admin/orders.php'));
        }
    } catch (RuntimeException $e) {
        flash('error', $e->getMessage());
    }
    redirect($self);
}

$items = order_items((int) $o['id']);
$events = order_events((int) $o['id']);
$custMsg = "Hello {$o['customer_name']}, this is Mojhometouch about your order {$o['ref']} (" . money($o['total']) . "). You can track it here: " . order_url($o, true);
$cancelled = $o['status'] === 'cancelled';

admin_header('Order ' . $o['ref'], 'orders');
?>
<div class="page-actions">
  <a class="link" href="<?= e(url('admin/orders.php')) ?>"><?= ic('back') ?> All orders</a>
  <div class="pa-right">
    <?= badge_status($o['status']) ?> <?= badge_pay($o['payment_status']) ?>
    <a class="btn btn-sm btn-line" href="<?= e(order_url($o)) ?>" target="_blank" rel="noopener"><?= ic('print') ?> Customer view / Print</a>
    <a class="btn btn-sm btn-wa" href="<?= e(wa_link($custMsg, $o['phone'])) ?>" target="_blank" rel="noopener"><?= wa_icon() ?> WhatsApp customer</a>
  </div>
</div>

<div class="cols wide">
  <div>
    <div class="card">
      <div class="card-h"><h2>Items</h2><span class="muted">Placed <?= fdate($o['created_at']) ?></span></div>
      <div class="tbl-wrap"><table class="tbl">
        <thead><tr><th>Product</th><th>Option</th><th>Price</th><th>Qty</th><th class="r">Total</th></tr></thead>
        <tbody>
        <?php foreach ($items as $it): ?>
          <tr><td><div class="thumb-cell"><img src="<?= e(img_url($it['image'])) ?>" alt=""><span><b><?= e($it['name']) ?></b><?php if ($it['product_id']): ?><small><a href="<?= e(url('admin/product-edit.php?id=' . $it['product_id'])) ?>">Edit product</a></small><?php endif; ?></span></div></td>
          <td><?= e($it['variant_name'] ?: '—') ?><?php if ($it['option_value']): ?><small><?= e(($it['option_label'] ?: 'Option') . ': ' . $it['option_value']) ?></small><?php endif; ?></td>
          <td><?= money($it['unit_price']) ?></td><td><?= (int) $it['qty'] ?></td><td class="r"><b><?= money($it['line_total']) ?></b></td></tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr><td colspan="4" class="r">Subtotal</td><td class="r"><?= money($o['subtotal']) ?></td></tr>
          <tr><td colspan="4" class="r">Delivery (<?= e($o['zone_name']) ?>)</td><td class="r"><?= money($o['delivery_fee']) ?></td></tr>
          <tr class="grand"><td colspan="4" class="r">Total</td><td class="r"><?= money($o['total']) ?></td></tr>
        </tfoot>
      </table></div>
    </div>

    <div class="card">
      <div class="card-h"><h2>Timeline</h2></div>
      <ul class="timeline">
        <?php foreach (array_reverse($events) as $ev): ?>
          <li class="<?= $ev['visible'] ? '' : 'internal' ?>"><b><?= e($ev['note']) ?></b><small><?= fdate($ev['created_at']) ?> · <?= e($ev['actor']) ?><?= $ev['status'] ? ' · ' . e(ORDER_STATUSES[$ev['status']] ?? '') : '' ?><?= $ev['visible'] ? '' : ' · internal' ?></small></li>
        <?php endforeach; ?>
      </ul>
      <form method="post" class="form-grid" style="margin-top:14px">
        <?= csrf_field() ?><input type="hidden" name="action" value="message">
        <label class="f full"><span>Add an update / note</span><textarea name="note" rows="2" placeholder="e.g. Dispatched with driver Emeka, 0803…"></textarea></label>
        <label class="chk"><input type="checkbox" name="visible" value="1" checked> Show to customer on their order page</label>
        <label class="chk"><input type="checkbox" name="notify" value="1"> Email customer<?= $o['email'] ? '' : ' (no email on file)' ?></label>
        <div class="full"><button class="btn btn-sm btn-dark" type="submit">Add update</button></div>
      </form>
    </div>
  </div>

  <div>
    <div class="card">
      <div class="card-h"><h2>Order status</h2></div>
      <?php if ($cancelled): ?>
        <p class="muted">This order is cancelled<?= $o['stock_restored'] ? ' and its stock was returned to inventory' : '' ?>. Cancelled orders are locked.</p>
        <form method="post" onsubmit="return confirm('Permanently delete this cancelled order?')" style="margin-top:12px"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><button class="btn btn-sm btn-danger" type="submit"><?= ic('trash') ?> Delete order</button></form>
      <?php else: ?>
      <form method="post" class="form-grid">
        <?= csrf_field() ?><input type="hidden" name="action" value="status">
        <label class="f full"><span>Change status</span><select name="status" id="statusSel">
          <?php foreach (ORDER_STATUSES as $k => $l): ?><option value="<?= $k ?>"<?= $o['status'] === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?>
        </select></label>
        <label class="f full"><span>Message to customer <small>(optional — a standard message is used if blank)</small></span><textarea name="note" rows="2"></textarea></label>
        <label class="chk full"><input type="checkbox" name="notify" value="1" checked> Email customer<?= $o['email'] ? '' : ' (no email on file)' ?></label>
        <div class="full"><button class="btn btn-sm btn-primary" type="submit" data-confirm-cancel>Update status</button></div>
      </form>
      <p class="hint" style="margin-top:10px">Cancelling returns stock to inventory and locks the order.</p>
      <?php endif; ?>
    </div>

    <div class="card">
      <div class="card-h"><h2>Payment</h2><?= badge_pay($o['payment_status']) ?></div>
      <div class="kv">
        <div><span>Method</span><b><?= e(payment_method_label($o['payment_method'])) ?></b></div>
        <div><span>Amount due</span><b><?= money($o['total']) ?></b></div>
        <?php if ($o['payment_ref']): ?><div><span>Reference</span><b class="mono"><?= e($o['payment_ref']) ?></b></div><?php endif; ?>
        <?php if ($o['paid_at']): ?><div><span>Paid</span><b><?= fdate($o['paid_at']) ?></b></div><?php endif; ?>
      </div>
      <?php if ($o['proof_file']): ?>
        <a class="proof" href="<?= e(url('admin/proof.php?id=' . $o['id'])) ?>" target="_blank" rel="noopener">
          <?php if (!str_ends_with($o['proof_file'], '.pdf')): ?><img src="<?= e(url('admin/proof.php?id=' . $o['id'])) ?>" alt="Payment receipt"><?php else: ?><?= ic('file') ?><?php endif; ?>
          <span>View uploaded receipt <?= ic('external') ?></span></a>
      <?php endif; ?>
      <?php if (!$cancelled): ?>
      <form method="post" class="form-grid" style="margin-top:14px">
        <?= csrf_field() ?><input type="hidden" name="action" value="payment">
        <label class="f full"><span>Set payment status</span><select name="payment_status"><?php foreach (PAYMENT_STATUSES as $k => $l): ?><option value="<?= $k ?>"<?= $o['payment_status'] === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></label>
        <label class="f"><span>Bank / POS ref <small>(optional)</small></span><input name="pay_ref" maxlength="120"></label>
        <label class="f"><span>Note <small>(optional)</small></span><input name="note" maxlength="300"></label>
        <div class="full" style="display:flex;gap:8px;flex-wrap:wrap">
          <button class="btn btn-sm btn-dark" type="submit">Save payment</button>
          <?php if ($o['payment_status'] !== 'paid'): ?><button class="btn btn-sm btn-ok" type="submit" name="payment_status" value="paid"><?= ic('tick') ?> Confirm paid</button><?php endif; ?>
        </div>
      </form>
      <?php endif; ?>
    </div>

    <div class="card">
      <div class="card-h"><h2>Customer</h2></div>
      <div class="kv">
        <div><span>Name</span><b><?= e($o['customer_name']) ?></b></div>
        <div><span>Phone</span><b><a href="tel:<?= e($o['phone']) ?>"><?= e($o['phone']) ?></a></b></div>
        <?php if ($o['email']): ?><div><span>Email</span><b><a href="mailto:<?= e($o['email']) ?>"><?= e($o['email']) ?></a></b></div><?php endif; ?>
        <div><span><?= $o['is_pickup'] ? 'Pickup' : 'Delivery' ?></span><b><?= e($o['zone_name']) ?></b></div>
        <?php if (!$o['is_pickup']): ?><div><span>Address</span><b><?= nl2br(e($o['address'])) ?><br><?= e($o['city']) ?>, <?= e($o['state']) ?></b></div><?php endif; ?>
        <?php if ($o['notes']): ?><div><span>Customer notes</span><b><?= nl2br(e($o['notes'])) ?></b></div><?php endif; ?>
        <?php $prev = (int) q_val('SELECT COUNT(*) FROM orders WHERE phone = ? AND id <> ?', [$o['phone'], $o['id']]); if ($prev): ?>
          <div><span>History</span><b><a href="<?= e(url('admin/orders.php?q=' . rawurlencode($o['phone']))) ?>"><?= $prev ?> other order<?= $prev > 1 ? 's' : '' ?> →</a></b></div>
        <?php endif; ?>
      </div>
    </div>

    <div class="card">
      <div class="card-h"><h2>Internal notes</h2></div>
      <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="notes">
        <textarea name="admin_notes" rows="4" class="ta" placeholder="Only visible to admins"><?= e($o['admin_notes']) ?></textarea>
        <button class="btn btn-sm btn-line" type="submit" style="margin-top:8px">Save notes</button></form>
    </div>
  </div>
</div>
<?php admin_footer();
