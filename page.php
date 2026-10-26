<?php
require_once __DIR__ . '/includes/bootstrap.php';
$pg = q_one('SELECT * FROM pages WHERE slug = ?', [qin('p')]);
if (!$pg) not_found();
$title = $pg['title'] . ' — ' . setting('business_name');
$desc = $pg['intro'] ?: excerpt($pg['body'], 155);
$canonical_query = 'p=' . rawurlencode($pg['slug']);
require __DIR__ . '/includes/layout/header.php';
echo page_hero('<span>' . e($pg['title']) . '</span>', e($pg['title']), e($pg['intro'] ?? ''), 'assets/img/pop-ceiling-sitting-room.jpg', true);
$others = q_all('SELECT slug, title FROM pages WHERE slug <> ? ORDER BY id', [$pg['slug']]);
?>
<section class="section"><div class="wrap" style="display:grid;grid-template-columns:minmax(0,1fr) 280px;gap:48px;align-items:start" id="pageGrid">
  <article class="prose rv"><?= $pg['body'] ?><p class="muted" style="margin-top:30px;font-size:.85rem">Last updated <?= fdate($pg['updated_at'], 'j F Y') ?></p></article>
  <aside class="panel" style="position:sticky;top:100px">
    <h2 style="font-size:1.35rem">Help &amp; Policies</h2>
    <ul style="list-style:none;display:grid;gap:10px">
      <?php foreach ($others as $o): ?><li><a class="link" href="<?= e(url('page.php?p=' . $o['slug'])) ?>"><?= e($o['title']) ?> →</a></li><?php endforeach; ?>
      <li><a class="link" href="<?= e(url('track.php')) ?>">Track an order →</a></li>
    </ul>
    <a class="btn btn-wa btn-block" style="margin-top:18px" href="<?= e(wa_link()) ?>" target="_blank" rel="noopener"><?= wa_icon() ?> Ask a question</a>
  </aside>
</div></section>
<style>@media(max-width:860px){#pageGrid{grid-template-columns:1fr!important}#pageGrid aside{position:static!important}}</style>
<?php require __DIR__ . '/includes/layout/footer.php';
