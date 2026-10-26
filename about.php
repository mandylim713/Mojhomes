<?php
require_once __DIR__ . '/includes/bootstrap.php';
$title = 'About Us — ' . setting('business_name');
$desc = 'Mojhometouch NIG LTD is a registered Nigerian classic paints and interior finishing company based in Nsukka, Enugu State.';
$active = 'about';
require __DIR__ . '/includes/layout/header.php';
echo page_hero('<span>About</span>', 'About Mojhometouch', 'A registered Nigerian company specialising in classic painting, interior finishing and interior design solutions.', 'assets/img/feature-wall-painting.jpg');
?>
<section class="section"><div class="wrap split">
  <div class="rv">
    <span class="eyebrow">Who We Are</span>
    <h2>Transforming spaces with quality workmanship.</h2>
    <p class="lead" style="margin:18px 0">Mojhometouch NIG LTD is a growing, professional brand transforming residential, commercial and institutional spaces with quality workmanship, durable materials and modern finishing.</p>
    <p class="muted">We are not simply a painting company. From producing our own <b>Moj Paints</b> to POP ceilings, screeding, curtains, furniture, cabinetry, epoxy flooring, stamp concrete and interior décor, we handle every layer of an interior — so our clients deal with one trusted team from start to finish.</p>
    <p class="muted">We are based in <b>Nsukka, Enugu State</b>, with a growing vision to serve clients across Nigeria.</p>
  </div>
  <div class="media rv"><img src="<?= e(url('assets/img/feature-wall-detail.jpg')) ?>" alt="Mojhometouch painter at work on a feature wall" loading="lazy"><div class="frame"></div></div>
</div></section>

<section class="section dark"><div class="wrap grid g2" style="gap:48px;align-items:center">
  <div class="rv"><span class="eyebrow">Our Vision</span><h2>Nigeria's trusted one-stop interior finishing brand.</h2></div>
  <div class="rv"><p class="lead">To become a trusted one-stop interior finishing and design brand in Nigeria — providing quality products and professional services while creating employment, training young people and contributing positively to our communities.</p>
  <ul class="ticks">
    <li><?= ic('check') ?><span>Quality products, starting with Moj Paints</span></li>
    <li><?= ic('check') ?><span>Professional, dependable services</span></li>
    <li><?= ic('check') ?><span>Jobs and skills for young Nigerians</span></li>
  </ul></div>
</div></section>

<section class="section"><div class="wrap">
  <div class="center" style="margin-bottom:48px"><span class="eyebrow">What We Stand For</span><h2>Our brand values</h2></div>
  <div class="grid g3">
    <?php foreach ([
      ['star', 'Quality', 'We use durable materials and proven methods for finishes that last.'],
      ['shield', 'Professionalism', 'Clear quotes, punctual teams and clean, safe worksites.'],
      ['brush', 'Creativity', 'Modern, tasteful designs tailored to each space.'],
      ['layers', 'Durability', 'Products and techniques chosen for Nigerian conditions.'],
      ['users', 'Trust', 'Honest advice and accountability from first visit to handover.'],
      ['check', 'Attention to Detail', 'Clean edges, smooth surfaces and careful finishing touches.'],
    ] as [$i, $t, $d]): ?>
      <article class="svc rv"><div class="ic"><?= ic($i) ?></div><h3><?= e($t) ?></h3><p><?= e($d) ?></p></article>
    <?php endforeach; ?>
  </div>
</div></section>

<section class="section sand"><div class="wrap split rev">
  <div class="media rv"><img src="<?= e(url('assets/img/door-finishing-white.jpg')) ?>" alt="Mojhometouch crew member finishing a panelled door" loading="lazy"><div class="frame"></div></div>
  <div class="rv"><span class="eyebrow">Our People</span><h2>Training the next generation of finishers.</h2>
  <p class="lead" style="margin-top:16px">Every Mojhometouch project creates work for skilled artisans and hands-on training for young people learning painting, POP and finishing — growing talent in our communities while delivering for our clients.</p>
  <a class="btn btn-dark" style="margin-top:28px" href="<?= e(url('contact.php')) ?>">Work With Us <?= ic('arrow') ?></a></div>
</div></section>
<?= cta_block() ?>
<?php require __DIR__ . '/includes/layout/footer.php';
