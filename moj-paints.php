<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/enquiry.php';

$res = null;
if (is_post()) $res = handle_enquiry('paint');

$paints = with_variants(q_all("SELECT p.*, c.name AS category_name FROM products p JOIN categories c ON c.id = p.category_id WHERE c.slug = 'moj-paints' AND p.active = 1 ORDER BY p.sort, p.id"));
$title = 'Moj Paints — High Quality Acrylic Paint | ' . setting('business_name');
$desc = 'Moj Paints by Mojhometouch: high quality acrylic emulsion, satin and matt paint for interiors and exteriors. Buy online direct from the producer with delivery across Nigeria.';
$active = 'paints';
$og_image = 'assets/img/moj-paints.jpg';
require __DIR__ . '/includes/layout/header.php';
echo page_hero('<span>Moj Paints</span>', 'Moj Paints', 'High quality acrylic paint, produced by Mojhometouch. Perfection only.', 'assets/img/feature-wall-detail.jpg');
?>
<section class="section"><div class="wrap">
  <div class="paint rv">
    <div class="txt"><span class="eyebrow">Product of Mojhometouch</span>
      <h2>Colour that <span>lasts.</span></h2>
      <p>Moj Paints is our own paint brand — made for smooth application, rich colour and dependable coverage. It's what our crews use on site, and it's available to homeowners, contractors and builders, direct from the producer.</p>
      <div class="chips"><span class="chip">EMULSION</span><span class="chip">SATIN</span><span class="chip">MATT</span><span class="chip">HIGH QUALITY ACRYLIC</span></div>
      <div style="display:flex;gap:12px;flex-wrap:wrap"><a class="btn btn-primary" href="#range">Shop the Range <?= ic('arrow') ?></a><a class="btn btn-ghost" href="#bulk">Bulk / Contractor Pricing</a></div></div>
    <div class="img"><img src="<?= e(url('assets/img/moj-paints.jpg')) ?>" alt="Moj Paints product range" loading="lazy"></div>
  </div>
</div></section>

<section id="range" class="section sand"><div class="wrap">
  <div class="center" style="margin-bottom:44px"><span class="eyebrow">The Range</span><h2>Choose your finish</h2><p class="lead" style="margin-top:12px">Pick a finish and size, tell us your colour, and we'll deliver — or collect from our Nsukka showroom.</p></div>
  <?php if ($paints): ?>
    <div class="pgrid c3"><?php foreach ($paints as $p) echo product_card($p); ?></div>
  <?php else: ?>
    <div class="empty"><?= ic('bucket') ?><h3>Coming soon</h3><p>Our paint range will be available to order online shortly.</p></div>
  <?php endif; ?>
</div></section>

<section class="section"><div class="wrap split">
  <div class="media rv"><img src="<?= e(url('assets/img/feature-wall-painting.jpg')) ?>" alt="Moj Paints in use on a Mojhometouch project" loading="lazy"><div class="frame"></div></div>
  <div class="rv"><span class="eyebrow">Why Moj Paints</span><h2>Trusted on our own projects.</h2>
  <ul class="ticks">
    <li><?= ic('check') ?><span><b>High quality acrylic</b> formulation</span></li>
    <li><?= ic('check') ?><span><b>Smooth application</b> with brush, roller or spray</span></li>
    <li><?= ic('check') ?><span><b>Rich, consistent colour</b> batch after batch</span></li>
    <li><?= ic('check') ?><span><b>Multiple bucket sizes</b> for touch-ups or full projects</span></li>
    <li><?= ic('check') ?><span><b>Direct from the producer</b> — bulk and contractor orders welcome</span></li>
  </ul>
  <div class="grid g3" style="gap:12px">
    <div class="svc" style="padding:18px"><b>Emulsion</b><p style="font-size:.85rem">Everyday walls, inside and out</p></div>
    <div class="svc" style="padding:18px"><b>Satin</b><p style="font-size:.85rem">Wipeable sheen for busy rooms</p></div>
    <div class="svc" style="padding:18px"><b>Matt</b><p style="font-size:.85rem">Flat, rich, hides flaws</p></div>
  </div></div>
</div></section>

<section id="bulk" class="section dark"><div class="wrap split" style="align-items:start">
  <div class="rv"><span class="eyebrow">Contractors &amp; Bulk Orders</span><h2>Buying for a project?</h2>
  <p class="lead" style="margin-top:16px">Get project pricing for large quantities, custom colours and scheduled deliveries. Send your request and our team will call you back.</p>
  <ul class="ticks"><li><?= ic('check') ?><span>Volume pricing for builders &amp; contractors</span></li><li><?= ic('check') ?><span>Custom colour requests</span></li><li><?= ic('check') ?><span>Scheduled site deliveries across Nigeria</span></li></ul></div>
  <?php if ($res && $res['ok']): ?>
    <div class="form rv success-box" style="color:var(--text)"><div class="ok-ring"><?= ic('tick') ?></div><h3>Request received!</h3><p class="muted">Thank you, <?= e($res['data']['name']) ?>. We'll call you shortly on <?= e($res['data']['phone']) ?>.</p>
      <p style="margin-top:20px"><a class="btn btn-wa" href="<?= e(wa_link(enquiry_wa_message('paint', $res['data']))) ?>" target="_blank" rel="noopener"><?= wa_icon() ?> Also send on WhatsApp</a></p></div>
  <?php else: $er = $res['errors'] ?? []; $dv = $res['data'] ?? []; ?>
  <form class="form rv" method="post" action="#bulk" style="color:var(--text)" novalidate>
    <?= csrf_field() ?><?= honeypot_field() ?>
    <?php if (isset($er['form'])): ?><div class="alert alert-error"><?= ic('alert') ?><?= e($er['form']) ?></div><?php endif; ?>
    <div class="fields">
      <div class="two">
        <label class="field<?= isset($er['name']) ? ' err' : '' ?>"><span>Full name <em>*</em></span><input name="name" value="<?= e($dv['name'] ?? '') ?>" required autocomplete="name"><?= field_err($er, 'name') ?></label>
        <label class="field<?= isset($er['phone']) ? ' err' : '' ?>"><span>Phone / WhatsApp <em>*</em></span><input name="phone" type="tel" value="<?= e($dv['phone'] ?? '') ?>" required autocomplete="tel"><?= field_err($er, 'phone') ?></label>
      </div>
      <div class="two">
        <label class="field"><span>Finish needed</span><select name="service"><?php foreach (['Emulsion', 'Satin', 'Matt', 'Mixed / not sure'] as $o): ?><option<?= ($dv['service'] ?? '') === $o ? ' selected' : '' ?>><?= $o ?></option><?php endforeach; ?></select></label>
        <label class="field"><span>Approx. quantity</span><input name="property" value="<?= e($dv['property'] ?? '') ?>" placeholder="e.g. 30 × 20L buckets"></label>
      </div>
      <label class="field"><span>Delivery location</span><input name="location" value="<?= e($dv['location'] ?? '') ?>" placeholder="Town / state"></label>
      <label class="field"><span>Colours &amp; notes</span><textarea name="details" placeholder="Colours, timeline, project type…"><?= e($dv['details'] ?? '') ?></textarea></label>
      <button class="btn btn-primary btn-lg" type="submit">Request Bulk Pricing <?= ic('arrow') ?></button>
    </div>
  </form>
  <?php endif; ?>
</div></section>
<?= cta_block('Need painters too?', 'Our professional crews can apply Moj Paints for you — request a quote for your project.') ?>
<?php require __DIR__ . '/includes/layout/footer.php';
