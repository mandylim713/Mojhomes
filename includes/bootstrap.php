<?php
declare(strict_types=1);

define('ROOT', dirname(__DIR__));
define('APP_VERSION', '1.0.0');

$__cfg = require ROOT . '/config.php';
if (is_file(ROOT . '/config.local.php')) {
    $__cfg = array_replace_recursive($__cfg, require ROOT . '/config.local.php');
}
define('CONFIG', $__cfg);
unset($__cfg);

date_default_timezone_set(CONFIG['timezone'] ?? 'Africa/Lagos');
foreach (['/storage', '/storage/logs', '/storage/proofs', '/uploads/products', '/uploads/gallery', '/uploads/categories'] as $__d) {
    if (!is_dir(ROOT . $__d)) @mkdir(ROOT . $__d, 0775, true);
}
error_reporting(E_ALL);
ini_set('display_errors', !empty(CONFIG['debug']) ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', ROOT . '/storage/logs/php-error.log');

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/icons.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/content.php';
require_once __DIR__ . '/cart.php';
require_once __DIR__ . '/orders.php';
require_once __DIR__ . '/mailer.php';
require_once __DIR__ . '/flutterwave.php';
require_once __DIR__ . '/upload.php';
require_once __DIR__ . '/partials.php';
require_once __DIR__ . '/backup.php';

set_exception_handler(function (Throwable $e): void {
    error_log((string) $e);
    if (!headers_sent()) http_response_code(500);
    if (!empty(CONFIG['debug'])) {
        echo '<pre style="padding:20px;white-space:pre-wrap">' . htmlspecialchars((string) $e) . '</pre>';
        return;
    }
    echo '<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Something went wrong</title>'
       . '<div style="font-family:system-ui,sans-serif;padding:80px 20px;text-align:center;color:#2b2b2b">'
       . '<h1 style="font-weight:600">Something went wrong</h1><p>Please try again in a moment.</p>'
       . '<p><a style="color:#b8892f" href="' . htmlspecialchars(url()) . '">Back to home</a></p></div>';
});

if (PHP_SAPI !== 'cli') {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_name('mojhome');
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => base_path(),
            'httponly' => true,
            'samesite' => 'Lax',
            'secure'   => is_https(),
        ]);
        session_start();
    }
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
}
