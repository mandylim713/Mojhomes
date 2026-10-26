<?php
require_once __DIR__ . '/includes/bootstrap.php';

$featured = with_variants(q_all('SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE p.active = 1 AND p.featured = 1 ORDER BY p.sort, p.id LIMIT 4'));
if (count($featured) < 4) {
    $featured = with_variants(q_all('SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE p.active = 1 ORDER BY p.featured DESC, p.sort, p.id LIMIT 4'));
}
$paints = with_variants(q_all("SELECT p.* FROM products p JOIN categories c ON c.id = p.category_id WHERE c.slug = 'moj-paints' AND p.active = 1 ORDER BY p.sort, p.id LIMIT 3"));
$recent = q_all("SELECT * FROM gallery WHERE active = 1 AND category IN ('painting','doors','curtains','ceilings') ORDER BY sort, id LIMIT 4");

$active = 'home';
require __DIR__ . '/includes/layout/header.php';
?>
<section class="hero">
  <div class="hero-bg" aria-hidden="true"><img src="<?= e(url('assets/img/pop-ceiling-sitting-room.jpg')) ?>" alt=""></div>
  <div class="wrap hero-grid">
    <div>
      <span class="eyebrow">Classic Paints &amp; Interior Finishing</span>
      <h1 class="hero-title">Spaces finished to <em>perfection.</em></h1>
      <p class="lead">From our own Moj Paints to POP ceilings, curtains, furniture and décor — Mojhometouch is your one-stop interior finishing partner for homes, offices and institutions across Nigeria.</p>
      <div class="ctas">
        <a class="btn btn-primary btn-lg" href="<?= e(url('contact.php#quote')) ?>">Get a Free Quote <?= ic('arrow') ?></a>
        <a class="btn btn-ghost btn-lg" href="<?= e(url('shop.php')) ?>"><?= ic('bag') ?> Shop Products</a>
      </div>
      <div class="hero-stats">
        <div><b>One-stop</b><span>Products + services</span></div>
        <div><b>Moj Paints</b><span>Our own paint brand</span></div>
        <div><b>Nationwide</b><span>Delivery &amp; projects</span></div>
      </div>
    </div>
    <div class="hero-media">
      <figure class="m1"><img src="<?= e(url('assets/img/curtains-sitting-room.jpg')) ?>" alt="Finished sitting room with POP ceiling and custom curtains by Mojhometouch" fetchpriority="high"></figure>
      <figure class="m2"><img src="<?= e(url('assets/img/feature-wall-painting.jpg')) ?>" alt="Mojhometouch painter finishing a lit feature wall"></figure>
      <div class="badge-float"><b>Perfection Only</b>Our promise on every job</div>
    </div>
  </div>
</section>
<?= strip_html() ?>

<section class="section"><div class="wrap">
  <div class="sec-head"><div><span class="eyebrow">What We Do</span><h2>Complete interior finishing,<br>under one roof.</h2></div>
  <a class="btn btn-dark" href="<?= e(url('services.php')) ?>">All Services <?= ic('arrow') ?></a></div>
  <div class="grid g3">
    <?php foreach (array_slice(services(), 0, 6) as [$sid, $icon, $t, $d]): ?>
    <article class="svc rv"><div class="ic"><?= ic($icon) ?></div><h3><?= e($t) ?></h3><p><?= e($d) ?></p><a class="more" href="<?= e(url('services.php#' . $sid)) ?>">Learn more →</a></article>
    <?php endforeach; ?>
  </div>
</div></section>

<section class="section sand"><div class="wrap">
  <div class="sec-head"><div><span class="eyebrow">Shop Online</span><h2>Bestsellers, delivered.</h2><p class="lead" style="margin-top:12px">Order Moj Paints, wall panels and décor online — pay securely and track your order to your door.</p></div>
  <a class="btn btn-dark" href="<?= e(url('shop.php')) ?>">Visit the Shop <?= ic('arrow') ?></a></div>
  <div class="pgrid">
    <?php foreach ($featured as $p) echo product_card($p); ?>
  </div>
  <div style="margin-top:36px"><?= trust_row() ?></div>
</div></section>

<section class="section"><div class="wrap split">
  <div class="media rv"><img src="<?= e(url('assets/img/pop-ceiling-sitting-room.jpg')) ?>" alt="Mojhometouch team on site under a finished POP ceiling" loading="lazy"><div class="frame"></div></div>
  <div class="rv">
    <span class="eyebrow">Why Mojhometouch</span>
    <h2>More than a painting company.</h2>
    <p class="lead" style="margin-top:16px">We combine quality products, skilled hands and an eye for design — so your project is handled by one accountable team, from the first coat to the final décor piece.</p>
    <ul class="ticks">
      <li><?= ic('check') ?><span><b>Quality materials</b> — including our own Moj Paints acrylic range.</span></li>
      <li><?= ic('check') ?><span><b>Professional workmanship</b> — trained, uniformed crews who respect your space.</span></li>
      <li><?= ic('check') ?><span><b>Attention to detail</b> — clean edges, smooth finishes, tidy sites.</span></li>
      <li><?= ic('check') ?><span><b>Durability</b> — finishes chosen to last in Nigerian conditions.</span></li>
    </ul>
    <a class="btn btn-dark" href="<?= e(url('about.php')) ?>">About Us <?= ic('arrow') ?></a>
  </div>
</div></section>

<section class="band" style="background-image:url('<?= e(url('assets/img/moon-wall-art.jpg')) ?>')"><div class="wrap center">
  <span class="eyebrow">Our Promise</span>
  <p class="band-quote">“Every wall, ceiling and finish we touch is held to one standard — <em>Perfection Only.</em>”</p>
  <a class="btn btn-primary btn-lg" href="<?= e(url('contact.php#quote')) ?>">Start Your Project <?= ic('arrow') ?></a>
</div></section>

<section class="section"><div class="wrap">
  <div class="paint rv">
    <div class="txt">
      <span class="eyebrow">Our Paint Brand</span>
      <h2>MOJ <span>Paints</span></h2>
      <p>High quality acrylic paint produced by Mojhometouch — rich colour, smooth application and lasting coverage for interiors and exteriors.</p>
      <?php if ($paints): ?>
      <div class="paint-mini">
        <?php foreach ($paints as $p): $pi = product_price_info($p); ?>
          <a href="<?= e(url('product.php?slug=' . rawurlencode($p['slug']))) ?>"><b><?= e(str_replace('Moj ', '', $p['name'])) ?></b><span><?= $pi['min'] > 0 ? 'From ' . money($pi['min']) : 'Price on request' ?></span></a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
      <div style="display:flex;gap:12px;flex-wrap:wrap">
        <a class="btn btn-primary" href="<?= e(url('shop.php?cat=moj-paints')) ?>">Buy Moj Paints <?= ic('arrow') ?></a>
        <a class="btn btn-ghost" href="<?= e(url('moj-paints.php')) ?>">Learn more</a>
      </div>
    </div>
    <div class="img"><img src="<?= e(url('assets/img/moj-paints.jpg')) ?>" alt="Moj Paints buckets — emulsion, satin and matt" loading="lazy"></div>
  </div>
</div></section>

<section class="section dark"><div class="wrap">
  <div class="sec-head"><div><span class="eyebrow">Recent Projects</span><h2>Work we're proud of.</h2></div>
  <a class="btn btn-primary" href="<?= e(url('projects.php')) ?>">Full Gallery <?= ic('arrow') ?></a></div>
  <div class="grid g4">
    <?php foreach ($recent as $g): ?>
    <a class="tile rv" href="<?= e(url('projects.php')) ?>"><div class="ph"><img src="<?= e(img_url($g['image'])) ?>" alt="<?= e($g['title']) ?>" loading="lazy"></div><div class="bd"><small><?= e(GALLERY_CATS[$g['category']] ?? '') ?></small><h3><?= e($g['title']) ?></h3></div></a>
    <?php endforeach; ?>
  </div>
</div></section>

<section class="section"><div class="wrap">
  <div class="center" style="margin-bottom:52px"><span class="eyebrow">How We Work</span><h2>Simple, clear, on schedule.</h2></div>
  <div class="steps">
    <div class="step rv"><h3>Consultation</h3><p>Call, WhatsApp or request a quote online. We discuss your needs, style and budget.</p></div>
    <div class="step rv"><h3>Site Visit &amp; Quote</h3><p>We inspect, measure and send a clear, itemised quotation.</p></div>
    <div class="step rv"><h3>Execution</h3><p>Our trained crew delivers with quality materials and tidy work.</p></div>
    <div class="step rv"><h3>Handover</h3><p>Final inspection with you — finished to perfection.</p></div>
  </div>
</div></section>

<section class="section dark"><div class="wrap">
  <div class="center" style="margin-bottom:44px"><span class="eyebrow">Our Values</span><h2>Built on trust and craft.</h2></div>
  <div class="vals rv">
    <div><b>Quality</b><p>Durable materials and finishes that stand the test of time.</p></div>
    <div><b>Professionalism</b><p>Punctual, uniformed, accountable teams on every site.</p></div>
    <div><b>Creativity</b><p>Modern designs tailored to how you live and work.</p></div>
    <div><b>Community</b><p>Creating jobs and training young people in the trade.</p></div>
  </div>
</div></section>
<?= cta_block() ?>
<?php require __DIR__ . '/includes/layout/footer.php';
