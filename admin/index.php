<?php
require_once __DIR__ . '/_init.php';
$me = require_admin();

if (is_post()) {
    csrf_check();
    if (pin('action') === 'prices_reviewed') { set_setting('sample_prices', '0'); flash('success', 'Great — prices marked as reviewed.'); }
    redirect(url('admin/'));
}

$monthStart = date('Y-m-01 00:00:00');
$today = date('Y-m-d 00:00:00');
$stats = [
    'revenue_month' => (int) q_val("SELECT COALESCE(SUM(total),0) FROM orders WHERE payment_status = 'paid' AND paid_at >= ?", [$monthStart]),
    'orders_month' => (int) q_val("SELECT COUNT(*) FROM orders WHERE created_at >= ? AND status <> 'cancelled'", [$monthStart]),
    'orders_today' => (int) q_val("SELECT COUNT(*) FROM orders WHERE created_at >= ?", [$today]),
    'to_fulfil' => (int) q_val("SELECT COUNT(*) FROM orders WHERE status IN ('pending','confirmed','processing','ready','shipped')"),
    'unpaid_value' => (int) q_val("SELECT COALESCE(SUM(total),0) FROM orders WHERE payment_status IN ('unpaid','review') AND status <> 'cancelled'"),
];
$c = admin_counts();
$low = (int) setting('low_stock', '5');
$lowItems = q_all("SELECT id, name, stock, NULL AS vname FROM products WHERE active = 1 AND stock IS NOT NULL AND stock <= ?
                   UNION ALL SELECT p.id, p.name, v.stock, v.name AS vname FROM product_variants v JOIN products p ON p.id = v.product_id WHERE p.active = 1 AND v.stock IS NOT NULL AND v.stock <= ?", [$low, $low]);
$recent = q_all('SELECT * FROM orders ORDER BY id DESC LIMIT 8');
$enq = q_all("SELECT * FROM enquiries ORDER BY id DESC LIMIT 5");

// last 14 days paid + placed
$days = [];
for ($i = 13; $i >= 0; $i--) $days[date('Y-m-d', strtotime("-$i day"))] = 0;
foreach (q_all("SELECT created_at, total FROM orders WHERE created_at >= ? AND status <> 'cancelled'", [array_key_first($days) . ' 00:00:00']) as $r) {
    $k = substr($r['created_at'], 0, 10);
    if (isset($days[$k])) $days[$k] += (int) $r['total'];
}
$max = max(1, max($days));

$checks = [
    ['Change the default admin password', setting('admin_default_pw') !== '1', 'account.php'],
    ['Add an email for order notifications', setting('notify_email') !== '' || setting('email') !== '', 'settings.php#business'],
    ['Add your company bank account (for bank-transfer orders)', setting('bank_account_number') !== '', 'settings.php#payments'],
    ['Review product prices & stock (sample prices were loaded)', setting('sample_prices') !== '1', 'products.php'],
    ['Review delivery zones and fees', (int) q_val("SELECT COUNT(*) FROM delivery_zones") > 0 && setting('sample_prices') !== '1', 'delivery.php'],
    ['Connect Flutterwave for online card/transfer payments (optional)', flw_enabled(), 'settings.php#payments'],
];
$pendingChecks = array_filter($checks, fn($x) => !$x[1]);

admin_header('Dashboard', 'dash');
$bst = backup_state();
?>
<?php if (!empty($bst['lineage_conflict']) || !empty($bst['restore_failed'])): ?>
<div class="alert alert-error"><?= ic('alert') ?><div><b>Your backup was not loaded when the server started.</b> Go to <a href="<?= e(url('admin/backups.php')) ?>">Backups</a> and restore from GitHub before taking new orders.</div></div>
<?php elseif (!empty($bst['last_error'])): ?>
<div class="alert alert-error"><?= ic('alert') ?><div><b>Backup problem:</b> <?= e($bst['last_error']) ?> — <a href="<?= e(url('admin/backups.php')) ?>">open Backups</a></div></div>
<?php elseif (!github_backup_enabled() && getenv('RENDER')): ?>
<div class="alert alert-warn"><?= ic('alert') ?><div><b>Automatic GitHub backup is not set up.</b> Without it, Render may wipe orders and uploads on redeploy. <a href="<?= e(url('admin/backups.php')) ?>">See Backups</a>.</div></div>
<?php endif; ?>
<p class="hello">Good <?= (int) date('G') < 12 ? 'morning' : ((int) date('G') < 17 ? 'afternoon' : 'evening') ?>, <?= e(explode(' ', $me['name'])[0]) ?>. Here's what's happening.</p>

<?php if ($pendingChecks): ?>
<div class="card setup">
  <div class="card-h"><h2><?= ic('check') ?> Finish setting up your store</h2><span class="muted"><?= count($checks) - count($pendingChecks) ?> of <?= count($checks) ?> done</span></div>
  <ul class="checklist">
    <?php foreach ($checks as [$label, $done, $href]): ?>
      <li class="<?= $done ? 'done' : '' ?>"><span class="tick"><?= ic('tick') ?></span><a href="<?= e(url('admin/' . $href)) ?>"><?= e($label) ?></a></li>
    <?php endforeach; ?>
  </ul>
  <?php if (setting('sample_prices') === '1'): ?>
    <form method="post" style="margin-top:14px"><?= csrf_field() ?><input type="hidden" name="action" value="prices_reviewed"><button class="btn btn-sm btn-line" type="submit">I've reviewed prices &amp; delivery fees</button></form>
  <?php endif; ?>
</div>
<?php endif; ?>

<div class="stats">
  <a class="stat" href="<?= e(url('admin/orders.php?pay=paid')) ?>"><span>Paid revenue · <?= date('F') ?></span><b><?= money($stats['revenue_month']) ?></b><small><?= $stats['orders_month'] ?> orders this month</small></a>
  <a class="stat" href="<?= e(url('admin/orders.php?status=pending')) ?>"><span>New orders to confirm</span><b><?= $c['pending'] ?></b><small><?= $stats['orders_today'] ?> placed today</small></a>
  <a class="stat warn" href="<?= e(url('admin/orders.php?pay=review')) ?>"><span>Payments to confirm</span><b><?= $c['review'] ?></b><small><?= money($stats['unpaid_value']) ?> awaiting payment</small></a>
  <a class="stat" href="<?= e(url('admin/enquiries.php?status=new')) ?>"><span>New quote requests</span><b><?= $c['enquiries'] ?></b><small><?= $stats['to_fulfil'] ?> orders in progress</small></a>
</div>

<div class="cols">
  <div class="card">
    <div class="card-h"><h2>Orders · last 14 days</h2><a class="link" href="<?= e(url('admin/orders.php')) ?>">All orders →</a></div>
    <div class="bars" role="img" aria-label="Order value per day for the last 14 days">
      <?php foreach ($days as $d => $v): ?>
        <div class="bar" title="<?= e(date('D j M', strtotime($d)) . ': ' . money($v)) ?>"><i style="height:<?= max(2, round($v / $max * 100)) ?>%"></i><span><?= date('j', strtotime($d)) ?></span></div>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="card">
    <div class="card-h"><h2>Low stock</h2><a class="link" href="<?= e(url('admin/products.php')) ?>">Products →</a></div>
    <?php if ($lowItems): ?>
      <ul class="mini-list"><?php foreach ($lowItems as $l): ?><li><a href="<?= e(url('admin/product-edit.php?id=' . $l['id'])) ?>"><?= e($l['name']) ?><?= $l['vname'] ? ' · ' . e($l['vname']) : '' ?></a><b class="<?= (int) $l['stock'] <= 0 ? 'neg' : '' ?>"><?= (int) $l['stock'] <= 0 ? 'Out' : (int) $l['stock'] . ' left' ?></b></li><?php endforeach; ?></ul>
    <?php else: ?><p class="muted">All tracked stock is healthy. Items with blank stock are treated as always available.</p><?php endif; ?>
  </div>
</div>

<div class="card">
  <div class="card-h"><h2>Recent orders</h2><a class="link" href="<?= e(url('admin/orders.php')) ?>">View all →</a></div>
  <?php if ($recent): ?>
  <div class="tbl-wrap"><table class="tbl">
    <thead><tr><th>Order</th><th>Date</th><th>Customer</th><th>Total</th><th>Payment</th><th>Status</th></tr></thead>
    <tbody><?php foreach ($recent as $o): ?>
      <tr data-href="<?= e(url('admin/order.php?id=' . $o['id'])) ?>"><td><a class="strong" href="<?= e(url('admin/order.php?id=' . $o['id'])) ?>"><?= e($o['ref']) ?></a></td><td><?= fdate($o['created_at'], 'j M, g:ia') ?></td><td><?= e($o['customer_name']) ?><small><?= e($o['phone']) ?></small></td><td><b><?= money($o['total']) ?></b></td><td><?= badge_pay($o['payment_status']) ?><small><?= e(payment_method_label($o['payment_method'])) ?></small></td><td><?= badge_status($o['status']) ?></td></tr>
    <?php endforeach; ?></tbody>
  </table></div>
  <?php else: ?><div class="empty-s"><?= ic('box') ?><p>No orders yet. Share your shop link: <a class="link" href="<?= e(url('shop.php')) ?>" target="_blank"><?= e(abs_url('shop.php')) ?></a></p></div><?php endif; ?>
</div>

<div class="card">
  <div class="card-h"><h2>Latest quote requests</h2><a class="link" href="<?= e(url('admin/enquiries.php')) ?>">View all →</a></div>
  <?php if ($enq): ?>
  <ul class="mini-list"><?php foreach ($enq as $q): ?><li><a href="<?= e(url('admin/enquiries.php#q' . $q['id'])) ?>"><b><?= e($q['name']) ?></b> — <?= e($q['service'] ?: ucfirst($q['type'])) ?><?= $q['location'] ? ' · ' . e($q['location']) : '' ?></a><span class="badge q-<?= e($q['status']) ?>"><?= e(ucfirst($q['status'])) ?></span></li><?php endforeach; ?></ul>
  <?php else: ?><p class="muted">No enquiries yet.</p><?php endif; ?>
</div>
<?php admin_footer();
