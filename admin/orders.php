<?php
require_once __DIR__ . '/_init.php';
require_admin();

$status = qin('status');
$pay = qin('pay');
$method = qin('method');
$search = mb_substr(qin('q'), 0, 80);
$where = ['1=1'];
$params = [];
if (isset(ORDER_STATUSES[$status])) { $where[] = 'status = ?'; $params[] = $status; }
if (isset(PAYMENT_STATUSES[$pay])) { $where[] = 'payment_status = ?'; $params[] = $pay; }
if (in_array($method, ['flutterwave', 'bank', 'pod'], true)) { $where[] = 'payment_method = ?'; $params[] = $method; }
if ($search !== '') { $where[] = '(ref LIKE ? OR customer_name LIKE ? OR phone LIKE ? OR email LIKE ?)'; $l = '%' . $search . '%'; array_push($params, $l, $l, $l, $l); }
$w = implode(' AND ', $where);

if (qin('export') === '1') {
    $rows = q_all("SELECT * FROM orders WHERE $w ORDER BY id DESC", $params);
    admin_csv('mojhometouch-orders-' . date('Ymd') . '.csv',
        ['Order', 'Date', 'Customer', 'Phone', 'Email', 'Delivery', 'Address', 'City', 'State', 'Subtotal', 'Delivery fee', 'Total', 'Payment method', 'Payment status', 'Status', 'Paid at'],
        array_map(fn($o) => [$o['ref'], $o['created_at'], $o['customer_name'], $o['phone'], $o['email'], $o['zone_name'], $o['address'], $o['city'], $o['state'], $o['subtotal'], $o['delivery_fee'], $o['total'], payment_method_label($o['payment_method']), PAYMENT_STATUSES[$o['payment_status']] ?? '', ORDER_STATUSES[$o['status']] ?? '', $o['paid_at']], $rows));
}

$total = (int) q_val("SELECT COUNT(*) FROM orders WHERE $w", $params);
$pg = paginate($total, 25, (int) qin('page', '1'));
$orders = q_all("SELECT o.*, (SELECT SUM(qty) FROM order_items i WHERE i.order_id = o.id) AS n FROM orders o WHERE $w ORDER BY o.id DESC LIMIT {$pg['per']} OFFSET {$pg['offset']}", $params);
$counts = [];
foreach (q_all('SELECT status, COUNT(*) AS n FROM orders GROUP BY status') as $r) $counts[$r['status']] = (int) $r['n'];

admin_header('Orders', 'orders');
$qs = fn(array $over) => '?' . http_build_query(array_filter(array_merge(['status' => $status, 'pay' => $pay, 'method' => $method, 'q' => $search], $over), fn($v) => $v !== '' && $v !== null));
?>
<div class="tabs">
  <a href="<?= e($qs(['status' => ''])) ?>" class="<?= $status === '' ? 'on' : '' ?>">All <em><?= array_sum($counts) ?></em></a>
  <?php foreach (ORDER_STATUSES as $k => $l): ?><a href="<?= e($qs(['status' => $k])) ?>" class="<?= $status === $k ? 'on' : '' ?>"><?= e($l) ?> <em><?= $counts[$k] ?? 0 ?></em></a><?php endforeach; ?>
</div>
<form class="toolbar" method="get">
  <input type="hidden" name="status" value="<?= e($status) ?>">
  <label class="search-in"><?= ic('search') ?><input type="search" name="q" value="<?= e($search) ?>" placeholder="Order no., name, phone or email"></label>
  <select name="pay" onchange="this.form.submit()"><option value="">Any payment</option><?php foreach (PAYMENT_STATUSES as $k => $l): ?><option value="<?= $k ?>"<?= $pay === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select>
  <select name="method" onchange="this.form.submit()"><option value="">Any method</option><?php foreach (['flutterwave', 'bank', 'pod'] as $m): ?><option value="<?= $m ?>"<?= $method === $m ? ' selected' : '' ?>><?= e(payment_method_label($m)) ?></option><?php endforeach; ?></select>
  <button class="btn btn-sm btn-dark" type="submit">Filter</button>
  <a class="btn btn-sm btn-line" href="<?= e($qs(['export' => '1'])) ?>"><?= ic('file') ?> Export CSV</a>
</form>

<div class="card flush">
<?php if ($orders): ?>
  <div class="tbl-wrap"><table class="tbl">
    <thead><tr><th>Order</th><th>Date</th><th>Customer</th><th>Items</th><th>Total</th><th>Payment</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($orders as $o): $href = url('admin/order.php?id=' . $o['id']); ?>
      <tr data-href="<?= e($href) ?>">
        <td><a class="strong" href="<?= e($href) ?>"><?= e($o['ref']) ?></a><?= $o['proof_file'] && $o['payment_status'] === 'review' ? '<small class="flag">Receipt uploaded</small>' : '' ?></td>
        <td><?= fdate($o['created_at'], 'j M Y') ?><small><?= fdate($o['created_at'], 'g:ia') ?></small></td>
        <td><?= e($o['customer_name']) ?><small><?= e($o['phone']) ?></small></td>
        <td><?= (int) $o['n'] ?></td>
        <td><b><?= money($o['total']) ?></b><small><?= e($o['zone_name']) ?></small></td>
        <td><?= badge_pay($o['payment_status']) ?><small><?= e(payment_method_label($o['payment_method'])) ?></small></td>
        <td><?= badge_status($o['status']) ?></td>
        <td><a class="btn btn-sm btn-line" href="<?= e($href) ?>">Open</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <?= page_links($pg, ['status' => $status, 'pay' => $pay, 'method' => $method, 'q' => $search]) ?>
<?php else: ?>
  <div class="empty-s"><?= ic('box') ?><p>No orders match these filters.</p></div>
<?php endif; ?>
</div>
<?php admin_footer();
