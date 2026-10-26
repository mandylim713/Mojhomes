<?php
require_once __DIR__ . '/includes/bootstrap.php';
$items = q_all('SELECT * FROM gallery WHERE active = 1 ORDER BY sort, id DESC');
$used = array_unique(array_column($items, 'category'));
$title = 'Projects & Gallery — ' . setting('business_name');
$desc = 'See Mojhometouch projects: painting, POP ceilings, doors, curtains, wall panels, marble sheets and interior décor.';
$active = 'projects';
require __DIR__ . '/includes/layout/header.php';
echo page_hero('<span>Projects</span>', 'Projects &amp; Gallery', 'A look at our finishing work, materials and décor. Tap any image to view it larger.', 'assets/img/curtains-sitting-room.jpg');
?>
<section class="section"><div class="wrap">
  <?php if ($items): ?>
  <div class="filters" role="toolbar" aria-label="Filter projects">
    <button class="on" data-f="all">All</button>
    <?php foreach (GALLERY_CATS as $k => $l): if (!in_array($k, $used, true)) continue; ?><button data-f="<?= e($k) ?>"><?= e($l) ?></button><?php endforeach; ?>
  </div>
  <div class="masonry">
    <?php foreach ($items as $g): ?>
      <figure data-cat="<?= e($g['category']) ?>"><img src="<?= e(img_url($g['image'])) ?>" alt="<?= e($g['title']) ?>" loading="lazy"><figcaption><small><?= e(GALLERY_CATS[$g['category']] ?? '') ?></small><?= e($g['title']) ?></figcaption></figure>
    <?php endforeach; ?>
  </div>
  <?php else: ?>
    <div class="empty"><?= ic('image') ?><h3>Projects coming soon</h3><p>We're updating our gallery.</p></div>
  <?php endif; ?>
</div></section>
<div class="lightbox" role="dialog" aria-modal="true" aria-label="Image viewer"><button class="x" aria-label="Close">×</button><button class="pv" aria-label="Previous">‹</button><img src="" alt=""><button class="nx" aria-label="Next">›</button><p></p></div>
<?= cta_block('Like what you see?', 'Let\'s create something just as beautiful in your space.') ?>
<?php require __DIR__ . '/includes/layout/footer.php';
