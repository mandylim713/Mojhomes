<?php
require_once __DIR__ . '/_init.php';

$next = qin('next') ?: pin('next');
$safeNext = (str_starts_with($next, url('admin/')) && !preg_match('~[\r\n\\\\]~', $next)) ? $next : url('admin/');
if (admin_user() && (time() - (int) ($_SESSION['admin_seen'] ?? 0)) < ADMIN_IDLE) redirect($safeNext);

$err = '';
$email = '';
if (is_post()) {
    csrf_check();
    $email = strtolower(pin('email'));
    $since = date('Y-m-d H:i:s', time() - 900);
    q('DELETE FROM login_attempts WHERE created_at < ?', [date('Y-m-d H:i:s', time() - 86400)]);
    $fails = (int) q_val('SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND created_at > ?', [client_ip(), $since]);
    if ($fails >= 5) {
        $err = 'Too many failed attempts. Please wait 15 minutes and try again.';
    } else {
        $a = q_one('SELECT * FROM admins WHERE LOWER(email) = ?', [$email]);
        if ($a && password_verify(pin('password'), $a['password_hash'])) {
            q('DELETE FROM login_attempts WHERE ip = ?', [client_ip()]);
            if (password_needs_rehash($a['password_hash'], PASSWORD_DEFAULT)) q('UPDATE admins SET password_hash = ? WHERE id = ?', [password_hash(pin('password'), PASSWORD_DEFAULT), $a['id']]);
            session_regenerate_id(true);
            $_SESSION['admin_id'] = (int) $a['id'];
            $_SESSION['admin_seen'] = time();
            q('UPDATE admins SET last_login = ? WHERE id = ?', [now(), $a['id']]);
            redirect($safeNext);
        }
        insert('login_attempts', ['ip' => client_ip(), 'created_at' => now()]);
        usleep(400000);
        $err = 'Incorrect email or password.';
    }
}
?><!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sign in · Mojhometouch Admin</title><meta name="robots" content="noindex, nofollow">
<link rel="icon" href="<?= e(url('favicon.png')) ?>" type="image/png">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600&family=Manrope:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(asset('assets/css/admin.css')) ?>">
</head><body class="login-body">
<div class="login-wrap">
  <div class="login-art" style="background-image:url('<?= e(url('assets/img/pop-ceiling-sitting-room.jpg')) ?>')"><div><b>Perfection Only.</b><span>Manage orders, products and your website in one place.</span></div></div>
  <form class="login-card" method="post" novalidate>
    <img src="<?= e(url('assets/img/logo.png')) ?>" alt="Mojhometouch" class="login-logo">
    <h1>Welcome back</h1>
    <p class="muted">Sign in to the Mojhometouch admin panel.</p>
    <?php foreach (take_flashes() as [$t, $m]): ?><div class="alert alert-<?= $t === 'error' ? 'error' : 'success' ?>"><?= e($m) ?></div><?php endforeach; ?>
    <?php if ($err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endif; ?>
    <?= csrf_field() ?><input type="hidden" name="next" value="<?= e($safeNext) ?>">
    <label class="f"><span>Email</span><input type="email" name="email" value="<?= e($email) ?>" autocomplete="username" required autofocus></label>
    <label class="f"><span>Password</span><input type="password" name="password" autocomplete="current-password" required></label>
    <button class="btn btn-primary btn-block" type="submit">Sign in</button>
    <a class="back" href="<?= e(url()) ?>">← Back to website</a>
  </form>
</div>
</body></html>
