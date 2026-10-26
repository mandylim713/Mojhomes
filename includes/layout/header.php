<?php
/**
 * Variables (all optional): $title, $desc, $active, $og_image, $og_type, $robots, $schema
 */
$biz      = setting('business_name', 'Mojhometouch NIG LTD');
$title    = $title ?? $biz . ' — Classic Paints & Interior Finishing';
$desc     = $desc ?? 'Mojhometouch NIG LTD — Moj Paints, professional painting, POP ceilings, curtains, furniture, epoxy flooring and interior décor. Based in Nsukka, Enugu State. Shop online with delivery across Nigeria.';
$active   = $active ?? '';
$og_image = $og_image ?? 'assets/img/og-image.jpg';
$og_type  = $og_type ?? 'website';
$reqPath  = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
$canon    = site_origin() . $reqPath . (isset($canonical_query) ? '?' . $canonical_query : '');
$nav = [
    ['index.php', 'Home', 'home'], ['about.php', 'About', 'about'], ['services.php', 'Services', 'services'],
    ['moj-paints.php', 'Moj Paints', 'paints'], ['shop.php', 'Shop', 'shop'], ['projects.php', 'Projects', 'projects'],
    ['contact.php', 'Contact', 'contact'],
];
$cc = cart_count();
$ogAbs = preg_match('~^https?://~', $og_image) ? $og_image : abs_url($og_image);
?><!DOCTYPE html>
<html lang="en" class="no-js">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?></title>
<meta name="description" content="<?= e($desc) ?>">
<?php if (!empty($robots)): ?><meta name="robots" content="<?= e($robots) ?>"><?php endif; ?>
<meta name="theme-color" content="#2B2B2B">
<link rel="canonical" href="<?= e($canon) ?>">
<meta property="og:site_name" content="<?= e($biz) ?>">
<meta property="og:type" content="<?= e($og_type) ?>">
<meta property="og:locale" content="en_NG">
<meta property="og:url" content="<?= e($canon) ?>">
<meta property="og:title" content="<?= e($title) ?>">
<meta property="og:description" content="<?= e($desc) ?>">
<meta property="og:image" content="<?= e($ogAbs) ?>">
<?php if ($og_image === 'assets/img/og-image.jpg'): ?>
<meta property="og:image:width" content="1200"><meta property="og:image:height" content="630">
<?php endif; ?>
<meta property="og:image:alt" content="<?= e($biz) ?> — Perfection Only">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= e($title) ?>">
<meta name="twitter:description" content="<?= e($desc) ?>">
<meta name="twitter:image" content="<?= e($ogAbs) ?>">
<link rel="icon" href="<?= e(url('favicon.png')) ?>" type="image/png">
<link rel="apple-touch-icon" href="<?= e(url('assets/img/apple-touch-icon.png')) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;0,700;1,500;1,600&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(asset('assets/css/style.css')) ?>">
<script>document.documentElement.classList.remove('no-js');</script>
<script type="application/ld+json"><?= json_encode([
    '@context' => 'https://schema.org', '@type' => 'HomeAndConstructionBusiness', 'name' => $biz,
    'slogan' => setting('tagline', 'Perfection Only'), 'url' => abs_url(), 'logo' => abs_url('assets/img/logo.png'),
    'image' => abs_url('assets/img/og-image.jpg'), 'telephone' => '+' . intl_phone(setting('phone')),
    'address' => ['@type' => 'PostalAddress', 'addressLocality' => 'Nsukka', 'addressRegion' => 'Enugu', 'addressCountry' => 'NG'],
    'areaServed' => 'Nigeria', 'openingHours' => 'Mo-Sa 08:00-18:00',
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
<?php if (!empty($schema)): ?><script type="application/ld+json"><?= json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script><?php endif; ?>
</head>
<body class="<?= e($body_class ?? '') ?>" data-csrf="<?= e(csrf_token()) ?>" data-cart-url="<?= e(url('cart.php')) ?>">
<a class="sr" href="#main">Skip to content</a>
<?php if (setting('announcement') !== ''): ?>
<div class="announce"><?= e(setting('announcement')) ?> · <a href="<?= e(url('shop.php')) ?>">Shop now</a></div>
<?php endif; ?>
<div class="topbar"><div class="wrap">
  <span><?= ic('pin') ?> <?= e(setting('address', 'Nsukka, Enugu State')) ?> — serving clients across Nigeria</span>
  <span><a href="<?= e(tel_href()) ?>"><?= e(setting('phone')) ?></a><span class="hide-sm">&nbsp;·&nbsp;<?= e(setting('hours')) ?></span>&nbsp;·&nbsp;<a href="<?= e(url('track.php')) ?>">Track order</a></span>
</div></div>
<header class="site"><div class="wrap">
  <a class="brand" href="<?= e(url()) ?>" aria-label="<?= e($biz) ?> home">
    <img src="<?= e(url('assets/img/logo.png')) ?>" alt="Mojhometouch logo" width="64" height="52">
    <span><b>MOJHOMETOUCH</b><small>Classic Paints &amp; Interior Finishing</small></span>
  </a>
  <nav class="main" id="mainnav" aria-label="Main">
    <?php foreach ($nav as [$h, $t, $k]): ?>
      <a href="<?= e(url($h)) ?>"<?= $k === $active ? ' class="active" aria-current="page"' : '' ?>><?= e($t) ?></a>
    <?php endforeach; ?>
    <a class="btn btn-primary nav-cta" href="<?= e(url('contact.php#quote')) ?>">Get a Quote</a>
  </nav>
  <div class="hdr-actions">
    <a class="btn btn-primary btn-sm btn-quote" href="<?= e(url('contact.php#quote')) ?>">Get a Quote</a>
    <a class="cart-btn" href="<?= e(url('cart.php')) ?>" aria-label="Cart (<?= $cc ?> items)"><?= ic('bag') ?><span class="cart-count" data-n="<?= $cc ?>"><?= $cc ?></span></a>
    <button class="burger" aria-label="Open menu" aria-expanded="false" aria-controls="mainnav"><?= ic('menu') ?></button>
  </div>
</div></header>
<div class="toasts" aria-live="polite">
<?php foreach (take_flashes() as [$t, $m]): ?>
  <div class="toast <?= $t === 'error' ? 'error' : '' ?>"><?= ic($t === 'error' ? 'alert' : 'check') ?><div class="tx"><?= e($m) ?></div><button class="x" aria-label="Dismiss"><?= ic('x') ?></button></div>
<?php endforeach; ?>
</div>
<main id="main">
