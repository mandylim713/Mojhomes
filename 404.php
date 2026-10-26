<?php
require_once __DIR__ . '/includes/bootstrap.php';
if (!headers_sent()) http_response_code(404);
$title = 'Page not found — ' . setting('business_name');
$robots = 'noindex';
require __DIR__ . '/includes/layout/header.php';
?>
<section class="section"><div class="wrap center" style="max-width:640px">
  <p style="font-family:var(--serif);font-size:7rem;line-height:1;color:var(--yellow);font-weight:600">404</p>
  <h1 style="font-size:2.6rem;margin:10px 0">This page wasn't found</h1>
  <p class="lead" style="margin:0 auto 30px">The page may have moved or no longer exists. Try one of these instead:</p>
  <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap">
    <a class="btn btn-primary" href="<?= e(url()) ?>">Home</a>
    <a class="btn btn-dark" href="<?= e(url('shop.php')) ?>">Shop</a>
    <a class="btn btn-line" href="<?= e(url('contact.php')) ?>">Contact</a>
  </div>
</div></section>
<?php require __DIR__ . '/includes/layout/footer.php';
