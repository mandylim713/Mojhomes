<?php
/**
 * Mojhometouch NIG LTD — site configuration
 * -------------------------------------------------------------
 * Every value can be set with an environment variable (Render → Environment),
 * or edited here directly for cPanel hosting. Environment variables win.
 *
 * SQLite works out of the box. Tables are created automatically on first visit.
 */
$env = static function (string $k, $default = null) {
    $v = getenv($k);
    return ($v === false || $v === '') ? $default : $v;
};

return [
    'db_driver'   => $env('DB_DRIVER', 'sqlite'),                        // 'sqlite' or 'mysql'
    'sqlite_path' => __DIR__ . '/storage/mojhometouch.sqlite',

    'mysql' => [
        'host' => $env('MYSQL_HOST', 'localhost'),
        'port' => (int) $env('MYSQL_PORT', 3306),
        'name' => $env('MYSQL_DATABASE', ''),
        'user' => $env('MYSQL_USER', ''),
        'pass' => $env('MYSQL_PASSWORD', ''),
    ],

    // Full site address, no trailing slash, e.g. https://mojhometouch.com — blank = auto-detect
    'site_url' => rtrim((string) $env('SITE_URL', ''), '/'),

    'timezone'        => $env('APP_TIMEZONE', 'Africa/Lagos'),
    'debug'           => $env('APP_DEBUG', '0') === '1',
    'mail_enabled'    => $env('MAIL_ENABLED', '1') === '1',
    'flutterwave_api' => $env('FLUTTERWAVE_API', 'https://api.flutterwave.com/v3'),

    // First admin account (used only when the database is created for the first time)
    'admin_email'    => $env('ADMIN_EMAIL', 'admin@mojhometouch.com'),
    'admin_password' => $env('ADMIN_PASSWORD', 'Moj@Admin2026'),

    // Outgoing email (SMTP). Needed on Render. Leave host blank on cPanel to use PHP mail().
    'smtp' => [
        'host'   => $env('SMTP_HOST', ''),
        'port'   => (int) $env('SMTP_PORT', 587),
        'user'   => $env('SMTP_USER', ''),
        'pass'   => $env('SMTP_PASS', ''),
        'secure' => $env('SMTP_SECURE', 'tls'),        // 'tls' (587), 'ssl' (465) or 'none'
        'from'   => $env('MAIL_FROM', ''),             // e.g. orders@mojhometouch.com
    ],

    // Automatic backups to a private GitHub repository (branch "db-backup" by default)
    'backup' => [
        'github_token'  => $env('GITHUB_BACKUP_TOKEN', ''),
        'github_repo'   => $env('GITHUB_BACKUP_REPO', ''),        // e.g. yourname/mojhometouch
        'github_branch' => $env('GITHUB_BACKUP_BRANCH', 'db-backup'),
        'github_api'    => $env('GITHUB_API', 'https://api.github.com'),
        'min_gap'       => (int) $env('BACKUP_MIN_GAP', 120),    // seconds between automatic backups
        'keep_local'    => (int) $env('BACKUP_KEEP_LOCAL', 14),   // timestamped copies kept in /backups
    ],
];
