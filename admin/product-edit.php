<?php
require_once __DIR__ . '/_init.php';
require_admin();

$id = (int) qin('id');
$p = $id ? q_one('SELECT * FROM products WHERE id = ?', [$id]) : null;
if ($id && !$p) { flash('error', 'Product not found.'); redirect(url('admin/products.php')); }
$cats = q_all('SELECT * FROM categories ORDER BY sort, id');
$vars = $p ? product_variants($id) : [];
$errors = [];

if (is_post()) {
    csrf_check();
    $d = [
        'name' => mb_substr(pin('name'), 0, 190),
        'category_id' => (int) pin('category_id') ?: null,
        'short_desc' => mb_substr(pin('short_desc'), 0, 255),
        'description' => pin('description'),
        'price' => naira_in(pin('price')),
        'compare_price' => pin('compare_price') === '' ? null : naira_in(pin('compare_price')),
        'unit' => mb_substr(pin('unit'), 0, 60) ?: null,
        'sku' => mb_substr(pin('sku'), 0, 80) ?: null,
        'stock' => int_or_null(pin('stock')),
        'option_label' => mb_substr(pin('option_label'), 0, 120) ?: null,
        'featured' => pin('featured') === '1' ? 1 : 0,
        'active' => pin('active') === '1' ? 1 : 0,
        'sort' => (int) pin('sort'),
    ];
    if ($d['name'] === '') $errors[] = 'Product name is required.';

    // variants from form
    $vIn = [];
    $names = $_POST['v_name'] ?? [];
    if (is_array($names)) {
        foreach ($names as $i => $vn) {
            $vn = trim((string) $vn);
            if ($vn === '') continue;
            $vIn[] = ['id' => (int) ($_POST['v_id'][$i] ?? 0), 'name' => mb_substr($vn, 0, 120), 'price' => naira_in((string) ($_POST['v_price'][$i] ?? '0')), 'stock' => int_or_null((string) ($_POST['v_stock'][$i] ?? '')), 'sort' => $i];
        }
    }
    foreach ($vIn as $v) if ($v['price'] <= 0) $errors[] = 'Each size/option needs a price (option "' . $v['name'] . '").';
    if ($vIn) { $d['price'] = min(array_column($vIn, 'price')); $d['stock'] = null; }

    $newMain = null; $newExtra = [];
    if (!$errors) {
        try {
            if (has_upload('image')) $newMain = upload_image($_FILES['image'], 'products');
            foreach (upload_list('images') as $f) $newExtra[] = upload_image($f, 'products');
        } catch (RuntimeException $e) { $errors[] = $e->getMessage(); }
    }

    if (!$errors) {
        $extra = $p ? (json_decode((string) $p['images'], true) ?: []) : [];
        $remove = array_map('strval', (array) ($_POST['remove_images'] ?? []));
        foreach ($extra as $k => $im) if (in_array($im, $remove, true)) { delete_upload($im); unset($extra[$k]); }
        $extra = array_values(array_merge($extra, $newExtra));
        $d['images'] = json_encode($extra);
        if ($newMain) { if ($p) delete_upload($p['image']); $d['image'] = $newMain; }
        elseif (!$p) $d['image'] = null;
        $d['slug'] = unique_slug('products', pin('slug') !== '' ? pin('slug') : $d['name'], $id);
        $d['updated_at'] = now();

        db()->beginTransaction();
        if ($p) update_row('products', $id, $d);
        else { $d['created_at'] = now(); $id = insert('products', $d); }
        $keep = [];
        foreach ($vIn as $v) {
            $exists = $v['id'] ? q_val('SELECT COUNT(*) FROM product_variants WHERE id = ? AND product_id = ?', [$v['id'], $id]) : 0;
            if ($exists) { update_row('product_variants', $v['id'], ['name' => $v['name'], 'price' => $v['price'], 'stock' => $v['stock'], 'sort' => $v['sort']]); $keep[] = $v['id']; }
            else $keep[] = insert('product_variants', ['product_id' => $id, 'name' => $v['name'], 'price' => $v['price'], 'stock' => $v['stock'], 'sort' => $v['sort']]);
        }
        if ($keep) q('DELETE FROM product_variants WHERE product_id = ? AND id NOT IN (' . implode(',', array_map('intval', $keep)) . ')', [$id]);
        else q('DELETE FROM product_variants WHERE product_id = ?', [$id]);
        db()->commit();
        flash('success', 'Product saved.');
        redirect(url('admin/product-edit.php?id=' . $id));
    }
    // re-show form with posted values
    $p = array_merge($p ?? ['id' => 0, 'image' => null, 'images' => '[]', 'slug' => ''], $d);
    $vars = $vIn;
}

$extraImgs = $p ? (json_decode((string) $p['images'], true) ?: []) : [];
admin_header($p && $id ? 'Edit: ' . $p['name'] : 'Add product', 'products');
$v = fn($k, $def = '') => e($p[$k] ?? $def);
?>
<div class="page-actions"><a class="link" href="<?= e(url('admin/products.php')) ?>"><?= ic('back') ?> All products</a>
<?php if ($id): ?><a class="btn btn-sm btn-line" href="<?= e(url('product.php?slug=' . rawurlencode($p['slug']))) ?>" target="_blank"><?= ic('eye') ?> View in shop</a><?php endif; ?></div>
<?php foreach ($errors as $er): ?><div class="alert alert-error"><?= ic('alert') ?><div><?= e($er) ?></div></div><?php endforeach; ?>

<form method="post" enctype="multipart/form-data" class="cols wide">
  <?= csrf_field() ?>
  <div>
    <div class="card">
      <div class="card-h"><h2>Product details</h2></div>
      <div class="form-grid">
        <label class="f full"><span>Name *</span><input name="name" value="<?= $v('name') ?>" required maxlength="190"></label>
        <label class="f full"><span>Short description <small>(shown on product cards)</small></span><input name="short_desc" value="<?= $v('short_desc') ?>" maxlength="255"></label>
        <label class="f full"><span>Full description <small>(basic HTML allowed: &lt;p&gt; &lt;ul&gt;&lt;li&gt; &lt;strong&gt;)</small></span><textarea name="description" rows="10" class="ta code"><?= $v('description') ?></textarea></label>
      </div>
    </div>

    <div class="card">
      <div class="card-h"><h2>Sizes / options &amp; prices</h2><span class="muted">Optional</span></div>
      <p class="hint">Add options when the price changes by size (e.g. 4 Litres, 20 Litres). Leave empty to use the single price on the right. Blank stock = unlimited.</p>
      <table class="tbl vtbl" id="vtbl">
        <thead><tr><th>Option name</th><th>Price (₦)</th><th>Stock</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($vars as $vr): ?>
          <tr><td><input type="hidden" name="v_id[]" value="<?= (int) ($vr['id'] ?? 0) ?>"><input name="v_name[]" value="<?= e($vr['name']) ?>" placeholder="e.g. 4 Litres"></td><td><input name="v_price[]" value="<?= (int) $vr['price'] ?>" inputmode="numeric"></td><td><input name="v_stock[]" value="<?= $vr['stock'] === null ? '' : (int) $vr['stock'] ?>" inputmode="numeric" placeholder="∞"></td><td><button type="button" class="btn btn-sm btn-line danger" data-rmrow aria-label="Remove"><?= ic('trash') ?></button></td></tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <template id="vrow"><tr><td><input type="hidden" name="v_id[]" value="0"><input name="v_name[]" placeholder="e.g. 4 Litres"></td><td><input name="v_price[]" inputmode="numeric" placeholder="0"></td><td><input name="v_stock[]" inputmode="numeric" placeholder="∞"></td><td><button type="button" class="btn btn-sm btn-line danger" data-rmrow aria-label="Remove"><?= ic('trash') ?></button></td></tr></template>
      <button type="button" class="btn btn-sm btn-line" id="addVar" style="margin-top:10px"><?= ic('plus') ?> Add option</button>
    </div>

    <div class="card">
      <div class="card-h"><h2>Images</h2></div>
      <div class="img-edit">
        <div class="main-img"><img src="<?= e(img_url($p['image'] ?? null)) ?>" alt="" id="mainPrev"><label class="btn btn-sm btn-line"><?= ic('upload') ?> <?= !empty($p['image']) ? 'Replace main image' : 'Upload main image' ?><input type="file" name="image" accept="image/*" hidden data-preview="#mainPrev"></label></div>
        <div>
          <p class="hint">Extra images (gallery). Tick to remove.</p>
          <div class="thumbs-edit">
            <?php foreach ($extraImgs as $im): ?><label><img src="<?= e(img_url($im)) ?>" alt=""><span><input type="checkbox" name="remove_images[]" value="<?= e($im) ?>"> remove</span></label><?php endforeach; ?>
          </div>
          <label class="btn btn-sm btn-line" style="margin-top:10px"><?= ic('plus') ?> Add images<input type="file" name="images[]" accept="image/*" multiple hidden data-count></label> <span class="hint" data-count-out></span>
        </div>
      </div>
      <p class="hint" style="margin-top:12px">JPG/PNG/WebP up to 10MB. Square photos on a clean background look best. Images are resized automatically.</p>
    </div>
  </div>

  <div>
    <div class="card">
      <div class="card-h"><h2>Publish</h2></div>
      <label class="chk"><input type="checkbox" name="active" value="1"<?= !isset($p['active']) || $p['active'] ? ' checked' : '' ?>> Live in shop</label>
      <label class="chk"><input type="checkbox" name="featured" value="1"<?= !empty($p['featured']) ? ' checked' : '' ?>> Bestseller (shown on homepage)</label>
      <button class="btn btn-primary btn-block" type="submit" style="margin-top:14px">Save product</button>
    </div>
    <div class="card">
      <div class="card-h"><h2>Pricing &amp; stock</h2></div>
      <div class="form-grid">
        <label class="f"><span>Price (₦)</span><input name="price" value="<?= (int) ($p['price'] ?? 0) ?>" inputmode="numeric"></label>
        <label class="f"><span>Was price (₦) <small>sale</small></span><input name="compare_price" value="<?= isset($p['compare_price']) && $p['compare_price'] !== null ? (int) $p['compare_price'] : '' ?>" inputmode="numeric"></label>
        <label class="f"><span>Stock <small>blank = unlimited</small></span><input name="stock" value="<?= isset($p['stock']) && $p['stock'] !== null ? (int) $p['stock'] : '' ?>" inputmode="numeric" placeholder="∞"></label>
        <label class="f"><span>Unit <small>e.g. per bucket</small></span><input name="unit" value="<?= $v('unit') ?>" maxlength="60"></label>
      </div>
      <p class="hint">Set price to 0 for “price on request” (customers can ask on WhatsApp). With options, the option prices &amp; stock are used.</p>
    </div>
    <div class="card">
      <div class="card-h"><h2>Organise</h2></div>
      <div class="form-grid">
        <label class="f full"><span>Category</span><select name="category_id"><option value="">— None —</option><?php foreach ($cats as $c): ?><option value="<?= (int) $c['id'] ?>"<?= (int) ($p['category_id'] ?? 0) === (int) $c['id'] ? ' selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select></label>
        <label class="f full"><span>Customer note field <small>e.g. “Preferred colour” (optional)</small></span><input name="option_label" value="<?= $v('option_label') ?>" maxlength="120"></label>
        <label class="f"><span>SKU</span><input name="sku" value="<?= $v('sku') ?>" maxlength="80"></label>
        <label class="f"><span>Sort order</span><input name="sort" value="<?= (int) ($p['sort'] ?? 0) ?>" inputmode="numeric"></label>
        <label class="f full"><span>URL slug <small>auto from name if blank</small></span><input name="slug" value="<?= $v('slug') ?>" maxlength="200"></label>
      </div>
    </div>
  </div>
</form>
<?php admin_footer();
