<?php
// ---------- Output ----------
function e($v): string
{
    return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function money($n): string
{
    return '₦' . number_format((int) $n);
}

function now(): string
{
    return date('Y-m-d H:i:s');
}

function fdate(?string $d, string $f = 'j M Y, g:ia'): string
{
    return $d ? date($f, strtotime($d)) : '—';
}

function excerpt(?string $s, int $n = 140): string
{
    $s = trim(preg_replace('~\s+~', ' ', strip_tags((string) $s)));
    return mb_strlen($s) > $n ? rtrim(mb_substr($s, 0, $n - 1)) . '…' : $s;
}

// ---------- URLs ----------
function is_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https'
        || (string) ($_SERVER['SERVER_PORT'] ?? '') === '443';
}

function base_path(): string
{
    static $bp = null;
    if ($bp !== null) return $bp;
    $site = trim((string) (CONFIG['site_url'] ?? ''));
    if ($site !== '') {
        $p = parse_url($site, PHP_URL_PATH) ?: '/';
        return $bp = rtrim($p, '/') . '/';
    }
    $root = str_replace('\\', '/', realpath(ROOT) ?: ROOT);
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $file = str_replace('\\', '/', realpath($_SERVER['SCRIPT_FILENAME'] ?? '') ?: '');
    if ($file !== '' && $script !== '' && str_starts_with($file, $root)) {
        $rel = substr($file, strlen($root));
        if ($rel !== '' && str_ends_with($script, $rel)) {
            return $bp = rtrim(substr($script, 0, -strlen($rel)), '/') . '/';
        }
    }
    $doc = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '');
    if ($doc !== '' && str_starts_with($root, $doc)) {
        return $bp = rtrim(substr($root, strlen($doc)), '/') . '/';
    }
    return $bp = '/';
}

function url(string $p = ''): string
{
    return base_path() . ltrim($p, '/');
}

function site_origin(): string
{
    $site = trim((string) (CONFIG['site_url'] ?? ''));
    if ($site !== '') {
        $u = parse_url($site);
        return ($u['scheme'] ?? 'https') . '://' . ($u['host'] ?? 'localhost') . (isset($u['port']) ? ':' . $u['port'] : '');
    }
    $host = preg_replace('~[^A-Za-z0-9.\-:\[\]]~', '', $_SERVER['HTTP_HOST'] ?? 'localhost');
    return (is_https() ? 'https' : 'http') . '://' . $host;
}

function abs_url(string $p = ''): string
{
    return site_origin() . url($p);
}

function asset(string $p): string
{
    $f = ROOT . '/' . ltrim($p, '/');
    return url($p) . (is_file($f) ? '?v=' . filemtime($f) : '');
}

function img_url(?string $p): string
{
    if (!$p) return url('assets/img/placeholder.jpg');
    if (preg_match('~^https?://~', $p)) return $p;
    return url($p);
}

function redirect(string $to): never
{
    header('Location: ' . $to);
    exit;
}

function same_host_referer(): ?string
{
    $r = $_SERVER['HTTP_REFERER'] ?? '';
    if ($r === '') return null;
    $host = strtolower(explode(':', $_SERVER['HTTP_HOST'] ?? '')[0]);
    return strtolower((string) parse_url($r, PHP_URL_HOST)) === $host ? $r : null;
}

function back(string $fallback = ''): never
{
    redirect(same_host_referer() ?? ($fallback ?: url()));
}

// ---------- Requests ----------
function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function is_ajax(): bool
{
    return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
}

function json_out(array $d, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($d, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function pin(string $k, string $d = ''): string
{
    $v = $_POST[$k] ?? $d;
    return is_string($v) ? trim($v) : $d;
}

function qin(string $k, string $d = ''): string
{
    $v = $_GET[$k] ?? $d;
    return is_string($v) ? trim($v) : $d;
}

function client_ip(): string
{
    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    if (getenv('TRUST_PROXY') === '1') { // behind Render / Cloudflare
        $cand = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_TRUE_CLIENT_IP'] ?? trim(explode(',', (string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? ''))[0]);
        if ($cand && filter_var($cand, FILTER_VALIDATE_IP)) $ip = $cand;
    }
    return substr($ip, 0, 64);
}

// ---------- CSRF / flash ----------
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    $t = $_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (is_string($t) && $t !== '' && hash_equals(csrf_token(), $t)) return;
    if (is_ajax()) json_out(['ok' => false, 'message' => 'Your session expired. Please refresh the page and try again.'], 403);
    http_response_code(403);
    exit('<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Session expired</title>'
        . '<div style="font-family:system-ui,sans-serif;padding:80px 20px;text-align:center"><h1>Session expired</h1>'
        . '<p>Please go back, refresh the page and try again.</p><p><a href="' . e(url()) . '">Home</a></p></div>');
}

function flash(string $type, string $msg): void
{
    $_SESSION['flash'][] = [$type, $msg];
}

function take_flashes(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return is_array($f) ? $f : [];
}

/** Simple per-session throttle: returns true when the limit is exceeded. */
function throttled(string $bucket, int $max, int $seconds): bool
{
    $now = time();
    $hits = array_filter($_SESSION['throttle'][$bucket] ?? [], fn($t) => $t > $now - $seconds);
    if (count($hits) >= $max) {
        $_SESSION['throttle'][$bucket] = $hits;
        return true;
    }
    $hits[] = $now;
    $_SESSION['throttle'][$bucket] = array_values($hits);
    return false;
}

function honeypot_tripped(): bool
{
    return !empty($_POST['website']);
}

function honeypot_field(): string
{
    return '<div class="hp" aria-hidden="true"><label>Website<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>';
}

// ---------- Strings / validation ----------
function slugify(string $s): string
{
    $s = strtolower(trim($s));
    $s = preg_replace('~[^a-z0-9]+~', '-', $s);
    return trim((string) $s, '-') ?: 'item';
}

function unique_slug(string $table, string $base, int $ignoreId = 0): string
{
    $slug = slugify($base);
    $try = $slug;
    $i = 1;
    while (q_val("SELECT COUNT(*) FROM $table WHERE slug = ? AND id <> ?", [$try, $ignoreId])) {
        $try = $slug . '-' . (++$i);
    }
    return $try;
}

function valid_email(string $e): bool
{
    return (bool) filter_var($e, FILTER_VALIDATE_EMAIL);
}

function norm_phone(string $p): string
{
    $d = preg_replace('~\D~', '', $p);
    if (str_starts_with($d, '234')) $d = '0' . substr($d, 3);
    return $d;
}

function valid_phone(string $p): bool
{
    $d = preg_replace('~\D~', '', $p);
    return strlen($d) >= 10 && strlen($d) <= 15;
}

function phone_match(string $a, string $b): bool
{
    $a = substr(norm_phone($a), -10);
    $b = substr(norm_phone($b), -10);
    return strlen($a) === 10 && $a === $b;
}

function intl_phone(string $p): string
{
    $n = preg_replace('~\D~', '', $p);
    if (str_starts_with($n, '0')) $n = '234' . substr($n, 1);
    return $n;
}

function wa_link(string $msg = '', ?string $to = null): string
{
    $n = intl_phone($to ?? setting('whatsapp', '2347031041249'));
    return 'https://wa.me/' . $n . ($msg !== '' ? '?text=' . rawurlencode($msg) : '');
}

function tel_href(): string
{
    return 'tel:+' . intl_phone(setting('phone', '07031041249'));
}

// ---------- Settings ----------
function settings_all(bool $refresh = false): array
{
    static $s = null;
    if ($s === null || $refresh) {
        $s = [];
        foreach (db()->query('SELECT name, value FROM settings') as $r) $s[$r['name']] = $r['value'];
    }
    return $s;
}

function setting(string $k, string $d = ''): string
{
    $s = settings_all();
    return (isset($s[$k]) && $s[$k] !== null && $s[$k] !== '') ? (string) $s[$k] : $d;
}

function set_setting(string $k, string $v): void
{
    if (q_val('SELECT COUNT(*) FROM settings WHERE name = ?', [$k])) {
        q('UPDATE settings SET value = ? WHERE name = ?', [$v, $k]);
    } else {
        q('INSERT INTO settings (name, value) VALUES (?, ?)', [$k, $v]);
    }
    settings_all(true);
}

// ---------- Pagination ----------
function paginate(int $total, int $per, int $page): array
{
    $pages = max(1, (int) ceil($total / $per));
    $page = max(1, min($page, $pages));
    return ['page' => $page, 'pages' => $pages, 'offset' => ($page - 1) * $per, 'per' => $per, 'total' => $total];
}

function page_links(array $p, array $params = []): string
{
    if ($p['pages'] <= 1) return '';
    $h = '<nav class="pager" aria-label="Pages">';
    for ($i = 1; $i <= $p['pages']; $i++) {
        $q = http_build_query(array_merge(array_filter($params, fn($v) => $v !== '' && $v !== null), ['page' => $i]));
        $h .= '<a href="?' . e($q) . '"' . ($i === $p['page'] ? ' class="on" aria-current="page"' : '') . '>' . $i . '</a>';
    }
    return $h . '</nav>';
}

function not_found(): never
{
    http_response_code(404);
    require ROOT . '/404.php';
    exit;
}

const NG_STATES = ['Abia', 'Adamawa', 'Akwa Ibom', 'Anambra', 'Bauchi', 'Bayelsa', 'Benue', 'Borno', 'Cross River', 'Delta', 'Ebonyi', 'Edo', 'Ekiti', 'Enugu', 'FCT Abuja', 'Gombe', 'Imo', 'Jigawa', 'Kaduna', 'Kano', 'Katsina', 'Kebbi', 'Kogi', 'Kwara', 'Lagos', 'Nasarawa', 'Niger', 'Ogun', 'Ondo', 'Osun', 'Oyo', 'Plateau', 'Rivers', 'Sokoto', 'Taraba', 'Yobe', 'Zamfara'];
