<?php
require_once __DIR__ . '/_init.php';
require_admin();

$edit = qin('edit');
if (is_post()) {
    csrf_check();
    $pg = q_one('SELECT * FROM pages WHERE slug = ?', [pin('slug')]);
    if ($pg) {
        $title = mb_substr(pin('title'), 0, 190);
        if ($title === '') flash('error', 'Title is required.');
        else {
            update_row('pages', (int) $pg['id'], ['title' => $title, 'intro' => mb_substr(pin('intro'), 0, 255), 'body' => pin('body'), 'updated_at' => now()]);
            flash('success', 'Page saved.');
        }
    }
    redirect(url('admin/pages.php?edit=' . rawurlencode(pin('slug'))));
}
$pages = q_all('SELECT * FROM pages ORDER BY id');
$cur = $edit !== '' ? q_one('SELECT * FROM pages WHERE slug = ?', [$edit]) : null;
admin_header($cur ? 'Edit: ' . $cur['title'] : 'Pages & Policies', 'pages');
?>
<?php if ($cur): ?>
<div class="page-actions"><a class="link" href="<?= e(url('admin/pages.php')) ?>"><?= ic('back') ?> All pages</a><a class="btn btn-sm btn-line" href="<?= e(url('page.php?p=' . $cur['slug'])) ?>" target="_blank"><?= ic('eye') ?> View page</a></div>
<form method="post" class="card form-grid">
  <?= csrf_field() ?><input type="hidden" name="slug" value="<?= e($cur['slug']) ?>">
  <label class="f full"><span>Title</span><input name="title" value="<?= e($cur['title']) ?>" required></label>
  <label class="f full"><span>Intro line</span><input name="intro" value="<?= e($cur['intro']) ?>"></label>
  <label class="f full"><span>Content <small>(HTML: &lt;h2&gt; headings, &lt;p&gt; paragraphs, &lt;ul&gt;&lt;li&gt; lists, &lt;strong&gt; bold, &lt;a href&gt; links)</small></span><textarea name="body" rows="24" class="ta code"><?= e($cur['body']) ?></textarea></label>
  <div class="full"><button class="btn btn-primary" type="submit">Save page</button></div>
</form>
<?php else: ?>
<p class="hint" style="margin-bottom:16px">These pages are linked in the website footer and at checkout. Review them with your business details and adjust as needed.</p>
<div class="card flush"><div class="tbl-wrap"><table class="tbl">
  <thead><tr><th>Page</th><th>Last updated</th><th></th></tr></thead>
  <tbody><?php foreach ($pages as $p): ?><tr><td><b><?= e($p['title']) ?></b><small><?= e($p['intro']) ?></small></td><td><?= fdate($p['updated_at'], 'j M Y') ?></td><td class="acts"><a class="btn btn-sm btn-line" href="?edit=<?= e($p['slug']) ?>"><?= ic('edit') ?> Edit</a><a class="btn btn-sm btn-line" href="<?= e(url('page.php?p=' . $p['slug'])) ?>" target="_blank"><?= ic('eye') ?></a></td></tr><?php endforeach; ?></tbody>
</table></div></div>
<?php endif; ?>
<?php admin_footer();
