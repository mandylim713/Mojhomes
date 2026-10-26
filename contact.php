<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/enquiry.php';

$res = null;
if (is_post()) $res = handle_enquiry('quote');
$er = $res['errors'] ?? [];
$dv = $res['data'] ?? ['service' => qin('service')];
$opts = quote_services();

$title = 'Contact & Request a Quote — ' . setting('business_name');
$desc = 'Contact Mojhometouch NIG LTD in Nsukka, Enugu State. Call ' . setting('phone') . ', chat on WhatsApp or request a quote for painting, POP, curtains, furniture and more.';
$active = 'contact';
require __DIR__ . '/includes/layout/header.php';
echo page_hero('<span>Contact</span>', 'Contact Us', 'Tell us about your project. We respond quickly on WhatsApp and phone.', 'assets/img/door-finishing-white.jpg');
?>
<section id="quote" class="section"><div class="wrap">
  <div class="contact-split">
    <?php if ($res && $res['ok']): ?>
      <div class="form rv success-box">
        <div class="ok-ring"><?= ic('tick') ?></div>
        <h2>Request received!</h2>
        <p class="muted" style="max-width:460px;margin:10px auto 0">Thank you, <?= e($res['data']['name']) ?>. Our team will contact you on <b><?= e($res['data']['phone']) ?></b> shortly to discuss your project and arrange a visit.</p>
        <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;margin-top:24px">
          <a class="btn btn-wa" href="<?= e(wa_link(enquiry_wa_message('quote', $res['data']))) ?>" target="_blank" rel="noopener"><?= wa_icon() ?> Also send on WhatsApp</a>
          <a class="btn btn-line" href="<?= e(url('projects.php')) ?>">View our projects</a>
        </div>
      </div>
    <?php else: ?>
    <form class="form rv" method="post" action="#quote" novalidate>
      <div><span class="eyebrow">Request a Quote</span><h2 style="font-size:2.2rem">Start your project</h2></div>
      <?= csrf_field() ?><?= honeypot_field() ?>
      <?php if (isset($er['form'])): ?><div class="alert alert-error"><?= ic('alert') ?><div><?= e($er['form']) ?></div></div><?php elseif ($er): ?><div class="alert alert-error"><?= ic('alert') ?><div>Please check the highlighted fields.</div></div><?php endif; ?>
      <div class="fields">
        <div class="two">
          <label class="field<?= isset($er['name']) ? ' err' : '' ?>"><span>Full name <em>*</em></span><input name="name" value="<?= e($dv['name'] ?? '') ?>" required autocomplete="name"><?= field_err($er, 'name') ?></label>
          <label class="field<?= isset($er['phone']) ? ' err' : '' ?>"><span>Phone / WhatsApp <em>*</em></span><input name="phone" type="tel" value="<?= e($dv['phone'] ?? '') ?>" required autocomplete="tel"><?= field_err($er, 'phone') ?></label>
        </div>
        <div class="two">
          <label class="field"><span>Service needed</span><select name="service"><?php foreach ($opts as $o): ?><option<?= ($dv['service'] ?? '') === $o ? ' selected' : '' ?>><?= e($o) ?></option><?php endforeach; ?></select></label>
          <label class="field"><span>Property type</span><select name="property"><?php foreach (['Residential', 'Commercial / Office', 'Institutional (school, church, hospital)', 'Other'] as $o): ?><option<?= ($dv['property'] ?? '') === $o ? ' selected' : '' ?>><?= e($o) ?></option><?php endforeach; ?></select></label>
        </div>
        <div class="two">
          <label class="field<?= isset($er['location']) ? ' err' : '' ?>"><span>Location <em>*</em></span><input name="location" value="<?= e($dv['location'] ?? '') ?>" placeholder="Town / area / state" required><?= field_err($er, 'location') ?></label>
          <label class="field"><span>Preferred start</span><input name="start_when" value="<?= e($dv['start_when'] ?? '') ?>" placeholder="e.g. next week"></label>
        </div>
        <label class="field<?= isset($er['email']) ? ' err' : '' ?>"><span>Email <span class="hint">(optional)</span></span><input name="email" type="email" value="<?= e($dv['email'] ?? '') ?>" autocomplete="email"><?= field_err($er, 'email') ?></label>
        <label class="field"><span>Project details</span><textarea name="details" placeholder="Rooms, sizes, finishes you like, budget range…"><?= e($dv['details'] ?? '') ?></textarea></label>
        <button class="btn btn-primary btn-lg" type="submit">Send Request <?= ic('arrow') ?></button>
        <p class="muted" style="font-size:.82rem">We reply within working hours. Prefer chat? <a class="link" href="<?= e(wa_link()) ?>" target="_blank" rel="noopener">WhatsApp us</a>.</p>
      </div>
    </form>
    <?php endif; ?>
    <aside class="info-card rv">
      <h3>Get in touch</h3>
      <div class="info-row"><?= ic('phone') ?><div><b>Phone</b><a href="<?= e(tel_href()) ?>"><?= e(setting('phone')) ?></a></div></div>
      <div class="info-row"><?= wa_icon() ?><div><b>WhatsApp</b><a href="<?= e(wa_link()) ?>" target="_blank" rel="noopener">Chat with us</a></div></div>
      <?php if (setting('email')): ?><div class="info-row"><?= ic('mail') ?><div><b>Email</b><a href="mailto:<?= e(setting('email')) ?>"><?= e(setting('email')) ?></a></div></div><?php endif; ?>
      <div class="info-row"><?= ic('pin') ?><div><b>Location</b><span><?= e(setting('address')) ?></span></div></div>
      <div class="info-row"><?= ic('clock') ?><div><b>Working hours</b><span><?= e(setting('hours')) ?></span></div></div>
      <div class="info-row"><?= ic('box') ?><div><b>Ordered a product?</b><a href="<?= e(url('track.php')) ?>">Track your order →</a></div></div>
      <a class="btn btn-primary" href="<?= e(tel_href()) ?>"><?= ic('phone') ?> Call Now</a>
    </aside>
  </div>
  <div class="map rv"><iframe title="Map — <?= e(setting('address')) ?>" src="https://www.google.com/maps?q=<?= rawurlencode(setting('map_query', 'Nsukka, Enugu, Nigeria')) ?>&output=embed" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe></div>
</div></section>

<section class="section sand"><div class="wrap" style="max-width:860px">
  <div class="center" style="margin-bottom:28px"><span class="eyebrow">FAQ</span><h2>Common questions</h2></div>
  <div class="faq">
    <details><summary>Do you work outside Nsukka?</summary><p>Yes. We are based in Nsukka, Enugu State, and take on projects and deliver products across Nigeria.</p></details>
    <details><summary>How do I order products online?</summary><p>Add items to your cart in the <a class="link" href="<?= e(url('shop.php')) ?>">shop</a>, check out with your details, and pay online, by bank transfer or on delivery. You'll get an order number to track your order. See <a class="link" href="<?= e(url('page.php?p=how-to-order')) ?>">How to Order</a>.</p></details>
    <details><summary>Can I buy Moj Paints without hiring you to paint?</summary><p>Absolutely. Moj Paints is sold directly to homeowners, contractors and builders, with bulk pricing available.</p></details>
    <details><summary>Do you offer site inspection before quoting?</summary><p>Yes — send your request with your location and we'll arrange a visit, then send a clear, itemised quotation.</p></details>
    <details><summary>Do you handle complete interiors?</summary><p>Yes — painting, POP ceilings, screeding, curtains, furniture, cabinetry, wall panels, flooring and décor, all from one team.</p></details>
  </div>
</div></section>
<?php require __DIR__ . '/includes/layout/footer.php';
