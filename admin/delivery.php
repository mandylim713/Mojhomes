<?php
require_once __DIR__ . '/_init.php';
require_admin();

if (is_post()) {
    csrf_check();
    $act = pin('action');
    $id = (int) pin('id');
    if ($act === 'save') {
        $d = ['name' => mb_substr(pin('name'), 0, 150), 'fee' => naira_in(pin('fee')), 'eta' => mb_substr(pin('eta'), 0, 120), 'is_pickup' => pin('is_pickup') === '1' ? 1 : 0, 'active' => pin('active') === '1' ? 1 : 0, 'sort' => (int) pin('sort')];
        if ($d['name'] === '') flash('error', 'Zone name is required.');
        else { $id ? update_row('delivery_zones', $id, $d) : insert('delivery_zones', $d); flash('success', 'Delivery option saved.'); }
    } elseif ($act === 'delete') {
        q('DELETE FROM delivery_zones WHERE id = ?', [$id]);
        flash('success', 'Delivery option deleted. Past orders keep their delivery details.');
    }
    redirect(url('admin/delivery.php'));
}
$zones = delivery_zones(false);
admin_header('Delivery Zones', 'delivery');
?>
<p class="hint" style="margin-bottom:16px">Customers choose one of these at checkout. The fee is added to the order total. Mark one as <b>pickup</b> so no address is required.</p>
<div class="card">
  <div class="zone-list">
    <?php foreach (array_merge($zones, [['id' => 0, 'name' => '', 'fee' => 0, 'eta' => '', 'is_pickup' => 0, 'active' => 1, 'sort' => count($zones)]]) as $z): ?>
    <form method="post" class="zone-row<?= $z['id'] ? '' : ' new' ?>">
      <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $z['id'] ?>">
      <label class="f"><span><?= $z['id'] ? 'Name' : 'New delivery option' ?></span><input name="name" value="<?= e($z['name']) ?>" placeholder="e.g. Abuja (FCT)" <?= $z['id'] ? 'required' : '' ?>></label>
      <label class="f sm"><span>Fee (₦)</span><input name="fee" value="<?= (int) $z['fee'] ?>" inputmode="numeric"></label>
      <label class="f"><span>Delivery time</span><input name="eta" value="<?= e($z['eta']) ?>" placeholder="e.g. 2–4 working days"></label>
      <label class="f xs"><span>Sort</span><input name="sort" value="<?= (int) $z['sort'] ?>" inputmode="numeric"></label>
      <div class="zone-chk"><label class="chk"><input type="checkbox" name="is_pickup" value="1"<?= $z['is_pickup'] ? ' checked' : '' ?>> Pickup</label><label class="chk"><input type="checkbox" name="active" value="1"<?= $z['active'] ? ' checked' : '' ?>> Active</label></div>
      <div class="zone-btns">
        <button class="btn btn-sm <?= $z['id'] ? 'btn-dark' : 'btn-primary' ?>" type="submit" name="action" value="save"><?= $z['id'] ? 'Save' : 'Add' ?></button>
        <?php if ($z['id']): ?><button class="btn btn-sm btn-line danger" type="submit" name="action" value="delete" onclick="return confirm('Delete this delivery option?')"><?= ic('trash') ?></button><?php endif; ?>
      </div>
    </form>
    <?php endforeach; ?>
  </div>
</div>
<?php admin_footer();
