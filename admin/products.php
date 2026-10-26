<?php
require_once __DIR__ . '/_init.php';
require_admin();

if (is_post()) {
    csrf_check();
    $id = (int) pin('id');
    $p = q_one('SELECT * FROM products WHERE id = ?', [$id]);
    if ($p) {
        $act = pin('action');
        if ($act === 'toggle') { q('UPDATE products SET active = 1 - active, updated_at = ? WHERE id = ?', [now(), $id]); flash('success', $p['name'] . ($p['active'] ? ' hidden from the shop.' : ' is now live in the shop.')); }
        if ($act === 'feature') { q('UPDATE products SET featured = 1 - featured WHERE id = ?', [$id]); flash('success', $p['name'] . ($p['featured'] ? ' removed from bestsellers.' : ' marked as a bestseller.')); }
        if ($act === 'delete') {
            q('DELETE FROM product_variants WHERE product_id = ?', [$id]);
            q('DELETE FROM products WHERE id = ?', [$id]);
            delete_upload($p['image']);
            foreach (json_decode((string) $p['images'], true) ?: [] as $im) delete_upload($im);
            flash('success', $p['name'] . ' deleted. Past orders keep their item details.');
        }
    }
    back(url('admin/products.php'));
}

$cats = q_all('SELECT * FROM categories ORDER BY sort, id');
$cat = (int) qin('cat');
$search = mb_substr(qin('q'), 0, 80);
$where = ['1=1']; $params = [];
if ($cat) { $where[] = 'p.category_id = ?'; $params[] = $cat; }
if ($search !== '') { $where[] = '(p.name LIKE ? OR p.sku LIKE ?)'; array_push($params, "%$search%", "%$search%"); }
$products = with_variants(q_all('SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE ' . implode(' AND ', $where) . ' ORDER BY p.sort, p.id', $params));
$low = (int) setting('low_stock', '5');

admin_header('Products', 'products');
?>
<?php if (setting('sample_prices') === '1'): ?><div class="alert alert-warn"><?= ic('alert') ?><div><b>Sample prices are loaded.</b> Open each product and set your real prices and stock before taking orders. Then confirm on the <a href="<?= e(url('admin/')) ?>">dashboard</a>.</div></div><?php endif; ?>
<form class="toolbar" method="get">
  <label class="search-in"><?= ic('search') ?><input type="search" name="q" value="<?= e($search) ?>" placeholder="Search products"></label>
  <select name="cat" onchange="this.form.submit()"><option value="">All categories</option><?php foreach ($cats as $c): ?><option value="<?= (int) $c['id'] ?>"<?= $cat === (int) $c['id'] ? ' selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select>
  <button class="btn btn-sm btn-dark" type="submit">Filter</button>
  <a class="btn btn-sm btn-primary push" href="<?= e(url('admin/product-edit.php')) ?>"><?= ic('plus') ?> Add product</a>
</form>

<div class="card flush">
<?php if ($products): ?>
  <div class="tbl-wrap"><table class="tbl">
    <thead><tr><th>Product</th><th>Category</th><th>Price</th><th>Stock</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($products as $p): $pi = product_price_info($p); $href = url('admin/product-edit.php?id=' . $p['id']); ?>
      <tr>
        <td><a class="thumb-cell" href="<?= e($href) ?>"><img src="<?= e(img_url($p['image'])) ?>" alt=""><span><b><?= e($p['name']) ?></b><small><?= $p['_variants'] ? count($p['_variants']) . ' options' : ($p['sku'] ? 'SKU ' . e($p['sku']) : '') ?><?= $p['featured'] ? ' · ★ Bestseller' : '' ?></small></span></a></td>
        <td><?= e($p['category_name'] ?? '—') ?></td>
        <td><?= $pi['min'] > 0 ? ($pi['from'] ? '<small>from</small> ' : '') . money($pi['min']) : '<span class="muted">On request</span>' ?></td>
        <td><?php
          if ($p['_variants']) {
              $parts = array_map(fn($v) => e($v['name']) . ': ' . ($v['stock'] === null ? '∞' : '<b class="' . ((int) $v['stock'] <= $low ? 'neg' : '') . '">' . (int) $v['stock'] . '</b>'), $p['_variants']);
              echo '<small style="display:block">' . implode('<br>', $parts) . '</small>';
          } else echo $p['stock'] === null ? '<span class="muted">Unlimited</span>' : '<b class="' . ((int) $p['stock'] <= $low ? 'neg' : '') . '">' . (int) $p['stock'] . '</b>';
        ?></td>
        <td><?= $p['active'] ? '<span class="badge st-delivered">Live</span>' : '<span class="badge">Hidden</span>' ?></td>
        <td class="acts">
          <a class="btn btn-sm btn-line" href="<?= e($href) ?>"><?= ic('edit') ?> Edit</a>
          <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $p['id'] ?>"><input type="hidden" name="action" value="toggle"><button class="btn btn-sm btn-line" type="submit"><?= $p['active'] ? 'Hide' : 'Publish' ?></button></form>
          <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $p['id'] ?>"><input type="hidden" name="action" value="feature"><button class="btn btn-sm btn-line" type="submit" title="Bestseller"><?= $p['featured'] ? '★' : '☆' ?></button></form>
          <a class="btn btn-sm btn-line" href="<?= e(url('product.php?slug=' . rawurlencode($p['slug']))) ?>" target="_blank" title="View in shop"><?= ic('eye') ?></a>
          <form method="post" onsubmit="return confirm('Delete <?= e(addslashes($p['name'])) ?>? This cannot be undone.')"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $p['id'] ?>"><input type="hidden" name="action" value="delete"><button class="btn btn-sm btn-line danger" type="submit" title="Delete"><?= ic('trash') ?></button></form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
<?php else: ?>
  <div class="empty-s"><?= ic('tag') ?><p>No products found. <a class="link" href="<?= e(url('admin/product-edit.php')) ?>">Add your first product →</a></p></div>
<?php endif; ?>
</div>
<?php admin_footer();
