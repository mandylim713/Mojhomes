<?php
require_once __DIR__ . '/_init.php';
require_admin();

if (is_post()) {
    csrf_check();
    $act = pin('action');
    $id = (int) pin('id');
    try {
        if ($act === 'save') {
            $name = mb_substr(pin('name'), 0, 120);
            if ($name === '') throw new RuntimeException('Category name is required.');
            $d = ['name' => $name, 'description' => mb_substr(pin('description'), 0, 500), 'sort' => (int) pin('sort'), 'active' => pin('active') === '1' ? 1 : 0];
            if (has_upload('image')) $d['image'] = upload_image($_FILES['image'], 'categories', 1600);
            if ($id) {
                $old = q_one('SELECT * FROM categories WHERE id = ?', [$id]);
                if (isset($d['image']) && $old) delete_upload($old['image']);
                $d['slug'] = unique_slug('categories', pin('slug') ?: $name, $id);
                update_row('categories', $id, $d);
            } else {
                $d['slug'] = unique_slug('categories', $name);
                insert('categories', $d);
            }
            flash('success', 'Category saved.');
        } elseif ($act === 'delete') {
            $n = (int) q_val('SELECT COUNT(*) FROM products WHERE category_id = ?', [$id]);
            if ($n) throw new RuntimeException("This category has $n product(s). Move or delete them first.");
            $old = q_one('SELECT * FROM categories WHERE id = ?', [$id]);
            if ($old) { delete_upload($old['image']); q('DELETE FROM categories WHERE id = ?', [$id]); }
            flash('success', 'Category deleted.');
        }
    } catch (RuntimeException $e) { flash('error', $e->getMessage()); }
    redirect(url('admin/categories.php'));
}

$cats = q_all('SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id) AS n FROM categories c ORDER BY c.sort, c.id');
admin_header('Categories', 'categories');
?>
<div class="cols wide">
  <div class="card flush">
    <div class="tbl-wrap"><table class="tbl">
      <thead><tr><th>Category</th><th>Products</th><th>Status</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($cats as $c): ?>
        <tr><td><div class="thumb-cell"><img src="<?= e(img_url($c['image'])) ?>" alt=""><span><b><?= e($c['name']) ?></b><small>/<?= e($c['slug']) ?></small></span></div></td>
        <td><a class="link" href="<?= e(url('admin/products.php?cat=' . $c['id'])) ?>"><?= (int) $c['n'] ?></a></td>
        <td><?= $c['active'] ? '<span class="badge st-delivered">Visible</span>' : '<span class="badge">Hidden</span>' ?></td>
        <td class="acts"><button class="btn btn-sm btn-line" type="button" data-toggle="#edit<?= (int) $c['id'] ?>"><?= ic('edit') ?> Edit</button>
          <form method="post" onsubmit="return confirm('Delete this category?')"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $c['id'] ?>"><button class="btn btn-sm btn-line danger" type="submit"><?= ic('trash') ?></button></form></td></tr>
        <tr id="edit<?= (int) $c['id'] ?>" class="edit-row" hidden><td colspan="4">
          <form method="post" enctype="multipart/form-data" class="form-grid">
            <?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
            <label class="f"><span>Name</span><input name="name" value="<?= e($c['name']) ?>" required></label>
            <label class="f"><span>Slug</span><input name="slug" value="<?= e($c['slug']) ?>"></label>
            <label class="f full"><span>Description</span><input name="description" value="<?= e($c['description']) ?>"></label>
            <label class="f"><span>Sort</span><input name="sort" value="<?= (int) $c['sort'] ?>"></label>
            <label class="f"><span>Banner image</span><input type="file" name="image" accept="image/*"></label>
            <label class="chk"><input type="checkbox" name="active" value="1"<?= $c['active'] ? ' checked' : '' ?>> Visible in shop</label>
            <div class="full"><button class="btn btn-sm btn-primary" type="submit">Save</button></div>
          </form></td></tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
  </div>
  <div class="card">
    <div class="card-h"><h2>Add category</h2></div>
    <form method="post" enctype="multipart/form-data" class="form-grid">
      <?= csrf_field() ?><input type="hidden" name="action" value="save">
      <label class="f full"><span>Name *</span><input name="name" required></label>
      <label class="f full"><span>Description</span><input name="description"></label>
      <label class="f"><span>Sort</span><input name="sort" value="<?= count($cats) ?>"></label>
      <label class="f"><span>Banner image</span><input type="file" name="image" accept="image/*"></label>
      <label class="chk full"><input type="checkbox" name="active" value="1" checked> Visible in shop</label>
      <div class="full"><button class="btn btn-sm btn-primary" type="submit"><?= ic('plus') ?> Add category</button></div>
    </form>
  </div>
</div>
<?php admin_footer();
