<?php
require_once __DIR__ . '/_init.php';
require_admin();

if (is_post()) {
    csrf_check();
    $act = pin('action');
    $id = (int) pin('id');
    try {
        $cat = array_key_exists(pin('category'), GALLERY_CATS) ? pin('category') : 'painting';
        if ($act === 'upload') {
            $files = upload_list('images');
            if (!$files) throw new RuntimeException('Please choose at least one image.');
            $title = mb_substr(pin('title'), 0, 190);
            $n = 0;
            $sort = (int) q_val('SELECT COALESCE(MIN(sort),0) FROM gallery') - 1;
            foreach ($files as $f) {
                $path = upload_image($f, 'gallery', 1800);
                insert('gallery', ['title' => $title !== '' ? $title : (GALLERY_CATS[$cat] . ' project'), 'category' => $cat, 'image' => $path, 'sort' => $sort--, 'active' => 1, 'created_at' => now()]);
                $n++;
            }
            flash('success', $n . ' image' . ($n > 1 ? 's' : '') . ' added to the gallery.');
        } elseif ($act === 'save') {
            update_row('gallery', $id, ['title' => mb_substr(pin('title'), 0, 190), 'category' => $cat, 'sort' => (int) pin('sort'), 'active' => pin('active') === '1' ? 1 : 0]);
            flash('success', 'Image updated.');
        } elseif ($act === 'delete') {
            $g = q_one('SELECT * FROM gallery WHERE id = ?', [$id]);
            if ($g) { delete_upload($g['image']); q('DELETE FROM gallery WHERE id = ?', [$id]); }
            flash('success', 'Image removed from the gallery.');
        }
    } catch (RuntimeException $e) { flash('error', $e->getMessage()); }
    redirect(url('admin/gallery.php'));
}

$items = q_all('SELECT * FROM gallery ORDER BY sort, id DESC');
admin_header('Projects Gallery', 'gallery');
?>
<div class="card">
  <div class="card-h"><h2>Upload project photos</h2></div>
  <form method="post" enctype="multipart/form-data" class="form-grid">
    <?= csrf_field() ?><input type="hidden" name="action" value="upload">
    <label class="f"><span>Caption</span><input name="title" placeholder="e.g. Living room POP ceiling, Nsukka"></label>
    <label class="f"><span>Category</span><select name="category"><?php foreach (GALLERY_CATS as $k => $l): ?><option value="<?= $k ?>"><?= e($l) ?></option><?php endforeach; ?></select></label>
    <label class="f full"><span>Photos <small>(select several at once)</small></span><input type="file" name="images[]" accept="image/*" multiple required></label>
    <div class="full"><button class="btn btn-sm btn-primary" type="submit"><?= ic('upload') ?> Upload</button></div>
  </form>
</div>
<div class="gal-grid">
  <?php foreach ($items as $g): ?>
  <form method="post" class="gal-item<?= $g['active'] ? '' : ' off' ?>">
    <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $g['id'] ?>">
    <img src="<?= e(img_url($g['image'])) ?>" alt="" loading="lazy">
    <input name="title" value="<?= e($g['title']) ?>" aria-label="Caption">
    <div class="gi-row">
      <select name="category" aria-label="Category"><?php foreach (GALLERY_CATS as $k => $l): ?><option value="<?= $k ?>"<?= $g['category'] === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select>
      <input name="sort" value="<?= (int) $g['sort'] ?>" aria-label="Sort" class="xs">
    </div>
    <label class="chk"><input type="checkbox" name="active" value="1"<?= $g['active'] ? ' checked' : '' ?>> Show on website</label>
    <div class="gi-row"><button class="btn btn-sm btn-dark" name="action" value="save" type="submit">Save</button><button class="btn btn-sm btn-line danger" name="action" value="delete" type="submit" onclick="return confirm('Remove this image?')"><?= ic('trash') ?></button></div>
  </form>
  <?php endforeach; ?>
</div>
<?php admin_footer();
