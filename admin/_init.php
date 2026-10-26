<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store');

const ADMIN_IDLE = 7200; // seconds

function admin_user(): ?array
{
    static $u = false;
    if ($u !== false) return $u;
    $id = (int) ($_SESSION['admin_id'] ?? 0);
    $u = $id ? q_one('SELECT id, name, email, created_at, last_login FROM admins WHERE id = ?', [$id]) : null;
    return $u;
}

function require_admin(): array
{
    $u = admin_user();
    $idle = time() - (int) ($_SESSION['admin_seen'] ?? 0);
    if (!$u || $idle > ADMIN_IDLE) {
        unset($_SESSION['admin_id'], $_SESSION['admin_seen']);
        if ($u) flash('error', 'You were signed out after a period of inactivity.');
        redirect(url('admin/login.php?next=' . rawurlencode($_SERVER['REQUEST_URI'] ?? '')));
    }
    $_SESSION['admin_seen'] = time();
    return $u;
}

function admin_counts(): array
{
    static $c = null;
    if ($c === null) {
        $c = [
            'pending' => (int) q_val("SELECT COUNT(*) FROM orders WHERE status = 'pending'"),
            'review' => (int) q_val("SELECT COUNT(*) FROM orders WHERE payment_status = 'review' AND status <> 'cancelled'"),
            'enquiries' => (int) q_val("SELECT COUNT(*) FROM enquiries WHERE status = 'new'"),
        ];
    }
    return $c;
}

function admin_header(string $title, string $active = ''): void
{
    $u = admin_user();
    $c = admin_counts();
    $nav = [
        ['index.php', 'Dashboard', 'grid', 'dash', 0],
        ['orders.php', 'Orders', 'box', 'orders', $c['pending'] + $c['review']],
        ['products.php', 'Products', 'tag', 'products', 0],
        ['categories.php', 'Categories', 'layers', 'categories', 0],
        ['delivery.php', 'Delivery Zones', 'truck', 'delivery', 0],
        ['gallery.php', 'Projects Gallery', 'image', 'gallery', 0],
        ['enquiries.php', 'Quotes & Enquiries', 'chat', 'enquiries', $c['enquiries']],
        ['pages.php', 'Pages & Policies', 'file', 'pages', 0],
        ['backups.php', 'Backups', 'shield', 'backups', (!empty(backup_state()['last_error']) || (!github_backup_enabled() && getenv('RENDER'))) ? '!' : 0],
        ['settings.php', 'Settings', 'settings', 'settings', 0],
        ['account.php', 'Admins & Account', 'users', 'account', 0],
    ];
    ?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?> · Mojhometouch Admin</title>
<meta name="robots" content="noindex, nofollow">
<link rel="icon" href="<?= e(url('favicon.png')) ?>" type="image/png">
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(asset('assets/css/admin.css')) ?>">
</head>
<body>
<aside class="side" id="side">
  <a class="side-brand" href="<?= e(url('admin/')) ?>"><img src="<?= e(url('assets/img/logo.png')) ?>" alt=""><span><b>MOJHOMETOUCH</b><small>Admin Panel</small></span></a>
  <nav>
    <?php foreach ($nav as [$h, $t, $i, $k, $n]): ?>
      <a href="<?= e(url('admin/' . $h)) ?>" class="<?= $k === $active ? 'on' : '' ?>"><?= ic($i) ?><span><?= e($t) ?></span><?php if ($n): ?><em><?= $n ?></em><?php endif; ?></a>
    <?php endforeach; ?>
  </nav>
  <div class="side-foot">
    <a href="<?= e(url()) ?>" target="_blank" rel="noopener"><?= ic('external') ?><span>View website</span></a>
    <a href="<?= e(url('admin/logout.php')) ?>"><?= ic('logout') ?><span>Sign out</span></a>
  </div>
</aside>
<div class="main">
  <header class="top">
    <button class="side-toggle" type="button" aria-label="Menu" onclick="document.getElementById('side').classList.toggle('open')"><?= ic('menu') ?></button>
    <h1><?= e($title) ?></h1>
    <div class="who"><span><?= e($u['name'] ?? '') ?></span><i><?= e(strtoupper(mb_substr($u['name'] ?? 'A', 0, 1))) ?></i></div>
  </header>
  <div class="content">
  <?php foreach (take_flashes() as [$t, $m]): ?>
    <div class="alert alert-<?= $t === 'error' ? 'error' : 'success' ?>"><?= ic($t === 'error' ? 'alert' : 'check') ?><div><?= e($m) ?></div></div>
  <?php endforeach;
}

function admin_footer(): void
{
    ?>
  </div>
  <footer class="afoot">Mojhometouch Admin · v<?= APP_VERSION ?> · Powered by <a href="https://digitalweboracleict.com" target="_blank" rel="noopener">DWO</a></footer>
</div>
<script src="<?= e(asset('assets/js/admin.js')) ?>" defer></script>
</body>
</html>
<?php
}

function admin_csv(string $filename, array $header, array $rows): never
{
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, $header);
    foreach ($rows as $r) fputcsv($out, array_map(fn($v) => is_string($v) && preg_match('~^[=+\-@]~', $v) ? "'" . $v : $v, $r));
    fclose($out);
    exit;
}

function int_or_null(string $v): ?int
{
    $v = trim($v);
    return $v === '' ? null : max(0, (int) preg_replace('~[^\d\-]~', '', $v));
}

function naira_in(string $v): int
{
    return max(0, (int) preg_replace('~[^\d]~', '', explode('.', $v)[0]));
}
