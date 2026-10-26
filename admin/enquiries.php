<?php
require_once __DIR__ . '/_init.php';
require_admin();
const ENQ_STATUSES = ['new' => 'New', 'contacted' => 'Contacted', 'won' => 'Won / Job booked', 'closed' => 'Closed'];

if (is_post()) {
    csrf_check();
    $id = (int) pin('id');
    if (pin('action') === 'status' && isset(ENQ_STATUSES[pin('status')])) { q('UPDATE enquiries SET status = ? WHERE id = ?', [pin('status'), $id]); flash('success', 'Enquiry updated.'); }
    if (pin('action') === 'delete') { q('DELETE FROM enquiries WHERE id = ?', [$id]); flash('success', 'Enquiry deleted.'); }
    back(url('admin/enquiries.php'));
}

$status = qin('status');
$type = qin('type');
$where = ['1=1']; $params = [];
if (isset(ENQ_STATUSES[$status])) { $where[] = 'status = ?'; $params[] = $status; }
if (in_array($type, ['quote', 'paint'], true)) { $where[] = 'type = ?'; $params[] = $type; }
$w = implode(' AND ', $where);

if (qin('export') === '1') {
    admin_csv('mojhometouch-enquiries-' . date('Ymd') . '.csv', ['Date', 'Type', 'Name', 'Phone', 'Email', 'Service', 'Property / Qty', 'Location', 'Start', 'Details', 'Status'],
        array_map(fn($q) => [$q['created_at'], $q['type'], $q['name'], $q['phone'], $q['email'], $q['service'], $q['property'], $q['location'], $q['start_when'], $q['details'], $q['status']], q_all("SELECT * FROM enquiries WHERE $w ORDER BY id DESC", $params)));
}
$total = (int) q_val("SELECT COUNT(*) FROM enquiries WHERE $w", $params);
$pg = paginate($total, 20, (int) qin('page', '1'));
$rows = q_all("SELECT * FROM enquiries WHERE $w ORDER BY id DESC LIMIT {$pg['per']} OFFSET {$pg['offset']}", $params);

admin_header('Quotes & Enquiries', 'enquiries');
?>
<div class="tabs">
  <a href="?type=<?= e($type) ?>" class="<?= $status === '' ? 'on' : '' ?>">All</a>
  <?php foreach (ENQ_STATUSES as $k => $l): ?><a href="?status=<?= $k ?>&type=<?= e($type) ?>" class="<?= $status === $k ? 'on' : '' ?>"><?= e($l) ?></a><?php endforeach; ?>
  <span class="push"></span>
  <a href="?status=<?= e($status) ?>&type=quote" class="<?= $type === 'quote' ? 'on' : '' ?>">Service quotes</a>
  <a href="?status=<?= e($status) ?>&type=paint" class="<?= $type === 'paint' ? 'on' : '' ?>">Paint bulk</a>
  <a href="?<?= e(http_build_query(['status' => $status, 'type' => $type, 'export' => 1])) ?>"><?= ic('file') ?> CSV</a>
</div>
<?php if (!$rows): ?><div class="card"><div class="empty-s"><?= ic('chat') ?><p>No enquiries here yet.</p></div></div><?php endif; ?>
<div class="enq-list">
<?php foreach ($rows as $q): $msg = 'Hello ' . $q['name'] . ', this is Mojhometouch following up on your ' . ($q['type'] === 'paint' ? 'Moj Paints' : ($q['service'] ?: 'project')) . ' request.'; ?>
  <article class="card enq" id="q<?= (int) $q['id'] ?>">
    <div class="enq-h">
      <div><b><?= e($q['name']) ?></b> <span class="badge q-<?= e($q['status']) ?>"><?= e(ENQ_STATUSES[$q['status']] ?? $q['status']) ?></span> <span class="badge"><?= $q['type'] === 'paint' ? 'Moj Paints bulk' : 'Service quote' ?></span>
        <small><?= fdate($q['created_at']) ?></small></div>
      <div class="acts">
        <a class="btn btn-sm btn-wa" href="<?= e(wa_link($msg, $q['phone'])) ?>" target="_blank" rel="noopener"><?= wa_icon() ?> WhatsApp</a>
        <a class="btn btn-sm btn-line" href="tel:<?= e($q['phone']) ?>"><?= ic('phone') ?> <?= e($q['phone']) ?></a>
      </div>
    </div>
    <div class="kv cols3">
      <?php foreach (['Service' => 'service', ($q['type'] === 'paint' ? 'Quantity' : 'Property') => 'property', 'Location' => 'location', 'Start' => 'start_when', 'Email' => 'email'] as $l => $k): if (!$q[$k]) continue; ?>
        <div><span><?= e($l) ?></span><b><?= e($q[$k]) ?></b></div>
      <?php endforeach; ?>
    </div>
    <?php if ($q['details']): ?><p class="enq-details"><?= nl2br(e($q['details'])) ?></p><?php endif; ?>
    <form method="post" class="enq-f"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $q['id'] ?>">
      <select name="status"><?php foreach (ENQ_STATUSES as $k => $l): ?><option value="<?= $k ?>"<?= $q['status'] === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select>
      <button class="btn btn-sm btn-dark" name="action" value="status" type="submit">Update</button>
      <button class="btn btn-sm btn-line danger" name="action" value="delete" type="submit" onclick="return confirm('Delete this enquiry?')"><?= ic('trash') ?></button>
    </form>
  </article>
<?php endforeach; ?>
</div>
<?= page_links($pg, ['status' => $status, 'type' => $type]) ?>
<?php admin_footer();
