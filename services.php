<?php
require_once __DIR__ . '/includes/bootstrap.php';
$title = 'Services — Painting, POP, Curtains, Furniture & Epoxy | ' . setting('business_name');
$desc = 'Professional painting, POP ceilings and screeding, curtains, furniture and cabinetry, wall panels, epoxy flooring, stamp concrete and interior décor by Mojhometouch.';
$active = 'services';
$svcs = services();
require __DIR__ . '/includes/layout/header.php';
echo page_hero('<span>Services</span>', 'Our Services', 'Everything your interior needs — products, skilled installation and finishing — delivered by one accountable team.', 'assets/img/feature-wall-detail.jpg');
?>
<section class="section tight"><div class="wrap grid g3">
  <?php foreach ($svcs as [$sid, $icon, $t, $d]): ?>
    <a class="svc rv" href="#<?= e($sid) ?>"><div class="ic"><?= ic($icon) ?></div><h3><?= e($t) ?></h3><p><?= e($d) ?></p><span class="more">Details →</span></a>
  <?php endforeach; ?>
  <a class="svc rv" href="<?= e(url('moj-paints.php')) ?>" style="background:var(--char);border-color:var(--char)"><div class="ic" style="background:var(--yellow);color:var(--ink)"><?= ic('bucket') ?></div><h3 style="color:#fff">Moj Paints</h3><p style="color:#BDB6A9">Our own high quality acrylic paint — emulsion, satin and matt. Order online.</p><span class="more" style="color:var(--yellow)">Shop Moj Paints →</span></a>
  <a class="svc rv" href="<?= e(url('contact.php#quote')) ?>"><div class="ic"><?= ic('chat') ?></div><h3>Not sure what you need?</h3><p>Tell us about your space and budget — we'll recommend the right finish and send a quote.</p><span class="more">Talk to us →</span></a>
</div></section>
<?php foreach ($svcs as $n => [$sid, $icon, $t, $d, $img, $list]): ?>
<section id="<?= e($sid) ?>" class="section<?= $n % 2 ? ' sand' : '' ?>"><div class="wrap split<?= $n % 2 ? ' rev' : '' ?>">
  <div class="media rv">
    <?php if ($img): ?><img src="<?= e(url($img)) ?>" alt="<?= e($t) ?> by Mojhometouch" loading="lazy"><?php else: ?><div class="ph-ic"><?= ic($icon) ?></div><?php endif; ?>
    <div class="frame"></div>
  </div>
  <div class="rv"><span class="eyebrow">Service 0<?= $n + 1 ?></span><h2><?= e($t) ?></h2><p class="lead" style="margin-top:16px"><?= e($d) ?></p>
    <ul class="ticks"><?php foreach ($list as $x): ?><li><?= ic('check') ?><span><?= e($x) ?></span></li><?php endforeach; ?></ul>
    <div style="display:flex;gap:12px;flex-wrap:wrap">
      <a class="btn btn-primary" href="<?= e(url('contact.php?service=' . rawurlencode($t) . '#quote')) ?>">Request a Quote <?= ic('arrow') ?></a>
      <a class="btn btn-wa" href="<?= e(wa_link('Hello Mojhometouch, I\'m interested in ' . $t . '.')) ?>" target="_blank" rel="noopener"><?= wa_icon() ?> Ask on WhatsApp</a>
    </div>
  </div>
</div></section>
<?php endforeach; ?>
<?= cta_block() ?>
<?php require __DIR__ . '/includes/layout/footer.php';
