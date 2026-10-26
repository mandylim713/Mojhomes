<?php
/**
 * Backups
 * -------------------------------------------------------------
 * 1. Local:  timestamped copies of the database in /backups (newest N kept).
 * 2. GitHub: the database + uploaded images + payment receipts are committed to the
 *            "backup/" folder on a separate branch (default "db-backup") of a PRIVATE
 *            GitHub repository. Every backup is a commit, so the branch history is a
 *            list of restore points.
 * 3. Restore: when the server starts with no database (new deploy, restart, lost disk),
 *            the latest backup is restored automatically.
 *
 * Safety: a backup is never pushed over a GitHub backup that belongs to a different
 * database (e.g. a fresh empty install after a failed restore) unless forced from Admin.
 */

function backup_cfg(): array
{
    return (CONFIG['backup'] ?? []) + ['github_token' => '', 'github_repo' => '', 'github_branch' => 'db-backup', 'github_api' => 'https://api.github.com', 'min_gap' => 120, 'keep_local' => 14];
}

function github_backup_enabled(): bool
{
    $c = backup_cfg();
    return $c['github_token'] !== '' && preg_match('~^[\w.-]+/[\w.-]+$~', (string) $c['github_repo']);
}

function backup_dir(): string
{
    $d = ROOT . '/backups';
    if (!is_dir($d)) @mkdir($d, 0775, true);
    return $d;
}

function backup_db_path(): string
{
    return (string) CONFIG['sqlite_path'];
}

// ---------- state (kept outside the DB so it doesn't trigger new backups) ----------
function backup_state(): array
{
    $f = ROOT . '/storage/backup-state.json';
    $s = is_file($f) ? json_decode((string) file_get_contents($f), true) : null;
    return is_array($s) ? $s : [];
}

function backup_state_save(array $patch): array
{
    $s = array_merge(backup_state(), $patch);
    @file_put_contents(ROOT . '/storage/backup-state.json', json_encode($s, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
    return $s;
}

function backup_log(string $msg): void
{
    @file_put_contents(ROOT . '/storage/logs/backup.log', '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL, FILE_APPEND);
    if (PHP_SAPI === 'cli') fwrite(STDERR, $msg . PHP_EOL);
}

// ---------- GitHub API ----------
/** @return array{0:int,1:array} */
function gh(string $method, string $path, ?array $body = null): array
{
    $c = backup_cfg();
    $url = rtrim($c['github_api'], '/') . $path;
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 90,
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $c['github_token'],
            'Accept: application/vnd.github+json',
            'X-GitHub-Api-Version: 2022-11-28',
            'User-Agent: mojhometouch-backup',
            'Content-Type: application/json',
        ],
    ]);
    if ($body !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_SLASHES));
    $res = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    if ($res === false) throw new RuntimeException('GitHub unreachable: ' . $err);
    $j = json_decode((string) $res, true);
    return [$code, is_array($j) ? $j : []];
}

function gh_ok(array $r, string $what): array
{
    [$code, $j] = $r;
    if ($code < 200 || $code >= 300) {
        $msg = $j['message'] ?? 'HTTP ' . $code;
        if ($code === 401) $msg = 'GitHub token is invalid or expired';
        if ($code === 403 || $code === 404) $msg .= ' — check GITHUB_BACKUP_REPO and that the token has "Contents: read & write" on it';
        throw new RuntimeException("$what failed: $msg");
    }
    return $j;
}

function git_blob_sha(string $content): string
{
    return sha1('blob ' . strlen($content) . "\0" . $content);
}

/** Remote backup tree: [path => sha] + head commit, or null when the branch does not exist yet. */
function gh_backup_tree(): ?array
{
    $c = backup_cfg();
    [$code, $ref] = gh('GET', '/repos/' . $c['github_repo'] . '/git/ref/heads/' . rawurlencode($c['github_branch']));
    if ($code === 404) return null;
    gh_ok([$code, $ref], 'Reading backup branch');
    $commit = gh_ok(gh('GET', '/repos/' . $c['github_repo'] . '/git/commits/' . $ref['object']['sha']), 'Reading backup commit');
    $tree = gh_ok(gh('GET', '/repos/' . $c['github_repo'] . '/git/trees/' . $commit['tree']['sha'] . '?recursive=1'), 'Reading backup files');
    $files = [];
    foreach ($tree['tree'] ?? [] as $t) if (($t['type'] ?? '') === 'blob') $files[$t['path']] = $t['sha'];
    return ['commit' => $ref['object']['sha'], 'tree' => $commit['tree']['sha'], 'date' => $commit['committer']['date'] ?? null, 'files' => $files];
}

function gh_blob(string $sha): string
{
    $c = backup_cfg();
    $b = gh_ok(gh('GET', '/repos/' . $c['github_repo'] . '/git/blobs/' . $sha), 'Downloading backup file');
    $raw = base64_decode(str_replace(["\n", "\r"], '', (string) ($b['content'] ?? '')), true);
    if ($raw === false) throw new RuntimeException('Backup file could not be decoded.');
    return $raw;
}

function gh_repo_is_private(): bool
{
    $c = backup_cfg();
    $st = backup_state();
    if (($st['repo_checked'] ?? '') === $c['github_repo'] && (time() - (int) ($st['repo_checked_at'] ?? 0)) < 86400) return (bool) $st['repo_private'];
    $r = gh_ok(gh('GET', '/repos/' . $c['github_repo']), 'Checking repository');
    $private = !empty($r['private']);
    backup_state_save(['repo_checked' => $c['github_repo'], 'repo_checked_at' => time(), 'repo_private' => $private]);
    return $private;
}

// ---------- snapshot ----------
function sqlite_valid(string $file): bool
{
    if (!is_file($file) || filesize($file) < 1024) return false;
    try {
        $p = new PDO('sqlite:' . $file, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $ok = $p->query('PRAGMA integrity_check')->fetchColumn() === 'ok'
            && $p->query("SELECT value FROM settings WHERE name = 'schema_version'")->fetchColumn() !== false;
        $p = null;
        return $ok;
    } catch (Throwable $e) {
        return false;
    }
}

function sqlite_meta(string $file): array
{
    try {
        $p = new PDO('sqlite:' . $file, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $m = [
            'db_uid' => (string) $p->query("SELECT value FROM settings WHERE name = 'db_uid'")->fetchColumn(),
            'orders' => (int) $p->query('SELECT COUNT(*) FROM orders')->fetchColumn(),
            'products' => (int) $p->query('SELECT COUNT(*) FROM products')->fetchColumn(),
            'enquiries' => (int) $p->query('SELECT COUNT(*) FROM enquiries')->fetchColumn(),
        ];
        $p = null;
        return $m;
    } catch (Throwable $e) {
        return ['db_uid' => '', 'orders' => 0, 'products' => 0, 'enquiries' => 0];
    }
}

/** Consistent copy of the live database. Returns [tmpFile, ext]. */
function backup_snapshot(): array
{
    if (!q_val("SELECT value FROM settings WHERE name = 'db_uid'")) set_setting('db_uid', bin2hex(random_bytes(8)));
    $tmp = ROOT . '/storage/snapshot-' . getmypid() . '-' . bin2hex(random_bytes(3));
    if (is_mysql()) {
        file_put_contents($tmp . '.sql', mysql_dump());
        return [$tmp . '.sql', 'sql'];
    }
    @unlink($tmp . '.sqlite');
    db()->exec('VACUUM INTO ' . db()->quote($tmp . '.sqlite'));
    return [$tmp . '.sqlite', 'sqlite'];
}

function mysql_dump(): string
{
    $out = "-- Mojhometouch MySQL backup " . date('c') . "\nSET FOREIGN_KEY_CHECKS=0;\n";
    foreach (q_all('SHOW TABLES') as $row) {
        $t = array_values($row)[0];
        $create = q_one("SHOW CREATE TABLE `$t`");
        $out .= "\nDROP TABLE IF EXISTS `$t`;\n" . $create['Create Table'] . ";\n";
        foreach (q_all("SELECT * FROM `$t`") as $r) {
            $vals = array_map(fn($v) => $v === null ? 'NULL' : db()->quote((string) $v), array_values($r));
            $out .= "INSERT INTO `$t` VALUES (" . implode(',', $vals) . ");\n";
        }
    }
    return $out . "SET FOREIGN_KEY_CHECKS=1;\n";
}

/** Files to include: [repoPath => absoluteFile] */
function backup_files(string $dbFile, string $ext): array
{
    $files = ['backup/database.' . $ext => $dbFile];
    foreach (['uploads' => ROOT . '/uploads', 'proofs' => ROOT . '/storage/proofs'] as $prefix => $dir) {
        if (!is_dir($dir)) continue;
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
        foreach ($it as $f) {
            if (!$f->isFile()) continue;
            $name = $f->getFilename();
            if ($name[0] === '.' || !preg_match('~\.(jpe?g|png|webp|pdf)$~i', $name)) continue;
            $rel = str_replace('\\', '/', substr($f->getPathname(), strlen($dir) + 1));
            $files['backup/' . $prefix . '/' . $rel] = $f->getPathname();
        }
    }
    ksort($files);
    return $files;
}

/** Cheap fingerprint to tell whether anything changed since the last backup. */
function backup_fingerprint(): string
{
    $parts = [];
    $db = backup_db_path();
    if (!is_mysql() && is_file($db)) $parts[] = filemtime($db) . ':' . filesize($db);
    if (is_mysql()) $parts[] = (string) q_val('SELECT MAX(updated_at) FROM orders') . (string) q_val('SELECT COUNT(*) FROM enquiries') . (string) q_val('SELECT MAX(updated_at) FROM products');
    foreach ([ROOT . '/uploads', ROOT . '/storage/proofs'] as $dir) {
        if (!is_dir($dir)) continue;
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
        foreach ($it as $f) if ($f->isFile()) $parts[] = $f->getFilename() . ':' . $f->getSize();
    }
    sort($parts);
    return sha1(implode('|', $parts));
}

function backup_local_copy(string $dbFile, string $ext): string
{
    $dir = backup_dir();
    $name = 'db-' . date('Ymd-His') . '.' . $ext;
    copy($dbFile, "$dir/$name");
    $all = glob("$dir/db-*.{sqlite,sql}", GLOB_BRACE) ?: [];
    rsort($all);
    foreach (array_slice($all, max(1, (int) backup_cfg()['keep_local'])) as $old) @unlink($old);
    return $name;
}

function backup_text_orders(): string
{
    try {
        $orders = q_all('SELECT * FROM orders ORDER BY id DESC');
        if (!$orders) return "No orders recorded yet.\n";
        $out = "MOJHOMETOUCH — ORDERS LOG (PLAIN TEXT EXPORT)\n";
        $out .= "Generated: " . date('Y-m-d H:i:s T') . "\n";
        $out .= "Total Orders: " . count($orders) . "\n\n";

        foreach ($orders as $o) {
            $items = q_all('SELECT * FROM order_items WHERE order_id = ? ORDER BY id', [$o['id']]);
            $out .= str_repeat('=', 80) . "\n";
            $out .= sprintf("ORDER REF: %s | DATE: %s\n", $o['ref'], $o['created_at']);
            $out .= sprintf("STATUS: %s | PAYMENT: %s (%s)\n", strtoupper($o['status']), strtoupper($o['payment_status']), payment_method_label($o['payment_method']));
            $out .= sprintf("CUSTOMER: %s | PHONE: %s | EMAIL: %s\n", $o['customer_name'], $o['phone'], $o['email'] ?: 'N/A');
            $out .= sprintf("DELIVERY: %s | ZONE: %s\n", !empty($o['is_pickup']) ? 'Pickup at Showroom' : ($o['address'] . ', ' . $o['city'] . ', ' . $o['state']), $o['zone_name'] ?: 'N/A');
            $out .= "ITEMS ORDERED:\n";
            foreach ($items as $it) {
                $opt = $it['option_value'] ? ' (' . ($it['option_label'] ?: 'Option') . ': ' . $it['option_value'] . ')' : '';
                $out .= sprintf("  - %s%s x %d @ ₦%s = ₦%s\n", $it['name'], $opt, $it['qty'], number_format($it['unit_price']), number_format($it['line_total']));
            }
            $out .= sprintf("SUBTOTAL: ₦%s | DELIVERY FEE: ₦%s | TOTAL: ₦%s\n", number_format($o['subtotal']), number_format($o['delivery_fee']), number_format($o['total']));
            if (!empty($o['notes'])) $out .= "CUSTOMER NOTES: " . $o['notes'] . "\n";
            if (!empty($o['admin_notes'])) $out .= "ADMIN NOTES: " . $o['admin_notes'] . "\n";
            if (!empty($o['payment_ref'])) $out .= "PAYMENT REF: " . $o['payment_ref'] . "\n";
            $out .= "\n";
        }
        return $out;
    } catch (Throwable $e) {
        return "Failed generating orders text log: " . $e->getMessage() . "\n";
    }
}

function backup_text_enquiries(): string
{
    try {
        $enqs = q_all('SELECT * FROM enquiries ORDER BY id DESC');
        if (!$enqs) return "No enquiries recorded yet.\n";
        $out = "MOJHOMETOUCH — ENQUIRIES & QUOTE REQUESTS LOG\n";
        $out .= "Generated: " . date('Y-m-d H:i:s T') . "\n";
        $out .= "Total Enquiries: " . count($enqs) . "\n\n";

        foreach ($enqs as $e) {
            $out .= str_repeat('=', 80) . "\n";
            $out .= sprintf("ENQUIRY #%d | DATE: %s | TYPE: %s | STATUS: %s\n", $e['id'], $e['created_at'], strtoupper($e['type']), strtoupper($e['status']));
            $out .= sprintf("NAME: %s | PHONE: %s | EMAIL: %s\n", $e['name'], $e['phone'], $e['email'] ?: 'N/A');
            $out .= sprintf("SERVICE: %s | LOCATION: %s | PROPERTY: %s | TIMELINE: %s\n", $e['service'] ?: 'N/A', $e['location'] ?: 'N/A', $e['property'] ?: 'N/A', $e['start_when'] ?: 'N/A');
            if (!empty($e['details'])) $out .= "DETAILS / MESSAGE:\n" . $e['details'] . "\n";
            $out .= "\n";
        }
        return $out;
    } catch (Throwable $e) {
        return "Failed generating enquiries text log: " . $e->getMessage() . "\n";
    }
}

function backup_text_summary(array $manifest): string
{
    try {
        $ordersCount = (int) q_val('SELECT COUNT(*) FROM orders');
        $ordersTotal = (int) (q_val('SELECT SUM(total) FROM orders WHERE payment_status = "paid"') ?: 0);
        $pendingOrders = (int) q_val('SELECT COUNT(*) FROM orders WHERE status = "pending"');
        $enquiriesCount = (int) q_val('SELECT COUNT(*) FROM enquiries');
        $productsCount = (int) q_val('SELECT COUNT(*) FROM products WHERE active = 1');

        $out = "================================================================================\n";
        $out .= "MOJHOMETOUCH NIG LTD — BACKUP OVERVIEW & HEALTH REPORT\n";
        $out .= "================================================================================\n";
        $out .= "Backup Date/Time : " . ($manifest['created_at'] ?? date('c')) . "\n";
        $out .= "Database Driver  : " . ($manifest['driver'] ?? 'sqlite') . "\n";
        $out .= "App Version      : " . ($manifest['app_version'] ?? '1.0.0') . "\n";
        $out .= "Trigger Reason   : " . ($manifest['reason'] ?? 'auto') . "\n\n";

        $out .= "--- STORE METRICS ---\n";
        $out .= "Total Orders Recorded : " . $ordersCount . "\n";
        $out .= "Paid Revenue          : ₦" . number_format($ordersTotal) . "\n";
        $out .= "Pending Orders        : " . $pendingOrders . "\n";
        $out .= "Total Customer Quotes : " . $enquiriesCount . "\n";
        $out .= "Active Products Listed: " . $productsCount . "\n\n";

        $out .= "--- FILES PRESERVED IN THIS GIT BACKUP ---\n";
        $out .= "1. backup/database." . ($manifest['driver'] ?? 'sqlite') . " -> Complete database snapshot\n";
        $out .= "2. backup/orders_log.txt -> Formatted plain text list of every order and customer contact\n";
        $out .= "3. backup/enquiries_log.txt -> Formatted plain text list of all quote requests & messages\n";
        $out .= "4. backup/manifest.json -> Machine-readable JSON metadata\n";
        $out .= "5. backup/uploads/ -> All uploaded product and gallery images\n";
        $out .= "6. backup/proofs/ -> All payment receipts uploaded by customers\n";
        $out .= "================================================================================\n";
        return $out;
    } catch (Throwable $e) {
        return "Backup Summary generated at " . date('c') . "\n";
    }
}

// ---------- push ----------
function backup_push_github(array $files, array $manifest, bool $force): array
{
    $c = backup_cfg();
    $repo = $c['github_repo'];
    if (!gh_repo_is_private()) throw new RuntimeException("Refusing to back up customer data to a PUBLIC repository ($repo). Make the repository private.");
    $remote = gh_backup_tree();
    if ($remote && isset($remote['files']['backup/manifest.json']) && !$force) {
        $rm = json_decode(gh_blob($remote['files']['backup/manifest.json']), true) ?: [];
        if (!empty($rm['db_uid']) && $rm['db_uid'] !== $manifest['db_uid']) {
            throw new RuntimeException('LINEAGE: The GitHub backup belongs to a different database (' . ($rm['orders'] ?? '?') . ' orders, saved ' . ($rm['created_at'] ?? '?') . '). Nothing was overwritten. Restore it from Admin → Backups, or choose "Overwrite GitHub backup".');
        }
    }
    $remoteFiles = $remote['files'] ?? [];
    $entries = [];
    $contents = [];
    foreach ($files as $path => $abs) $contents[$path] = (string) file_get_contents($abs);
    $contents['backup/manifest.json'] = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    $contents['backup/orders_log.txt'] = backup_text_orders();
    $contents['backup/enquiries_log.txt'] = backup_text_enquiries();
    $contents['backup/latest_summary.txt'] = backup_text_summary($manifest);
    $contents['backup/README.md'] = "# Mojhometouch automatic backup\n\nThis branch is written automatically by the website. Do not edit.\n\n"
        . "- `database." . ($manifest['driver'] ?? 'sqlite') . "` — the full site database (orders, products, customers, settings)\n"
        . "- `orders_log.txt` — human-readable plain text log of all customer orders\n"
        . "- `enquiries_log.txt` — human-readable plain text log of all quotes and enquiries\n"
        . "- `latest_summary.txt` — business status and backup overview in plain text\n"
        . "- `uploads/` — product, category and gallery images\n"
        . "- `proofs/` — payment receipts uploaded by customers\n\n"
        . "Every commit on this branch is a restore point. Latest: " . $manifest['created_at'] . "\n";
    $alwaysUpdate = ['backup/manifest.json', 'backup/README.md', 'backup/orders_log.txt', 'backup/enquiries_log.txt', 'backup/latest_summary.txt'];
    $changed = 0;
    foreach ($contents as $path => $data) {
        if (($remoteFiles[$path] ?? null) === git_blob_sha($data)) continue;
        if (in_array($path, $alwaysUpdate, true)) { $entries[$path] = $data; continue; }
        $entries[$path] = $data;
        $changed++;
    }
    $deleted = [];
    foreach ($remoteFiles as $path => $sha) if (str_starts_with($path, 'backup/') && !isset($contents[$path])) $deleted[] = $path;
    if ($remote && $changed === 0 && !$deleted) return ['status' => 'unchanged', 'commit' => $remote['commit']];

    $tree = [];
    foreach ($entries as $path => $data) {
        $blob = gh_ok(gh('POST', "/repos/$repo/git/blobs", ['content' => base64_encode($data), 'encoding' => 'base64']), 'Uploading ' . basename($path));
        $tree[] = ['path' => $path, 'mode' => '100644', 'type' => 'blob', 'sha' => $blob['sha']];
    }
    foreach ($deleted as $path) $tree[] = ['path' => $path, 'mode' => '100644', 'type' => 'blob', 'sha' => null];
    $treeReq = ['tree' => $tree];
    if ($remote) $treeReq['base_tree'] = $remote['tree'];
    $newTree = gh_ok(gh('POST', "/repos/$repo/git/trees", $treeReq), 'Creating backup tree');
    $msg = 'Backup ' . $manifest['created_at'] . ' — ' . $manifest['orders'] . ' orders, ' . $manifest['products'] . ' products' . ($changed ? " ($changed file" . ($changed > 1 ? 's' : '') . ' changed)' : '');
    $commit = gh_ok(gh('POST', "/repos/$repo/git/commits", ['message' => $msg, 'tree' => $newTree['sha'], 'parents' => $remote ? [$remote['commit']] : []]), 'Creating backup commit');
    if ($remote) gh_ok(gh('PATCH', "/repos/$repo/git/refs/heads/" . rawurlencode($c['github_branch']), ['sha' => $commit['sha'], 'force' => $force]), 'Updating backup branch');
    else gh_ok(gh('POST', "/repos/$repo/git/refs", ['ref' => 'refs/heads/' . $c['github_branch'], 'sha' => $commit['sha']]), 'Creating backup branch');
    return ['status' => 'pushed', 'commit' => $commit['sha'], 'files' => $changed, 'deleted' => count($deleted)];
}

/**
 * Run a backup. $force skips the "nothing changed" and timing checks; $overwrite also
 * replaces a GitHub backup that belongs to a different database.
 */
function backup_run(bool $force = false, bool $overwrite = false, string $reason = 'auto'): array
{
    $lock = fopen(ROOT . '/storage/backup.lock', 'c');
    if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) return ['status' => 'busy'];
    try {
        $st = backup_state();
        if (!empty($st['lineage_conflict']) && !$overwrite) {
            if (!$force) return ['status' => 'blocked', 'error' => $st['last_error'] ?? ''];
        }
        $fp = backup_fingerprint();
        if (!$force) {
            if (($st['fingerprint'] ?? '') === $fp && empty($st['last_error'])) return ['status' => 'unchanged'];
            if (time() - (int) ($st['last_attempt'] ?? 0) < (int) backup_cfg()['min_gap']) return ['status' => 'waiting'];
        }
        backup_state_save(['last_attempt' => time()]);
        [$tmp, $ext] = backup_snapshot();
        try {
            $meta = $ext === 'sqlite' ? sqlite_meta($tmp) : ['db_uid' => setting('db_uid'), 'orders' => (int) q_val('SELECT COUNT(*) FROM orders'), 'products' => (int) q_val('SELECT COUNT(*) FROM products'), 'enquiries' => (int) q_val('SELECT COUNT(*) FROM enquiries')];
            $manifest = $meta + ['created_at' => date('Y-m-d H:i:s T'), 'driver' => $ext, 'app_version' => APP_VERSION, 'reason' => $reason];
            $local = backup_local_copy($tmp, $ext);
            $res = ['status' => 'local', 'local' => $local];
            $patch = ['fingerprint' => $fp, 'last_local' => $local, 'last_local_at' => time(), 'last_error' => '', 'lineage_conflict' => false];
            if (github_backup_enabled()) {
                try {
                    $r = backup_push_github(backup_files($tmp, $ext), $manifest, $overwrite);
                    $res = $r + ['local' => $local];
                    $patch += ['last_push_at' => time(), 'last_commit' => $r['commit'], 'last_push_status' => $r['status']];
                } catch (Throwable $e) {
                    $patch['fingerprint'] = ''; // retry next time
                    $patch['last_error'] = $e->getMessage();
                    $patch['lineage_conflict'] = str_starts_with($e->getMessage(), 'LINEAGE');
                    $res = ['status' => 'error', 'error' => $e->getMessage(), 'local' => $local];
                }
            }
            backup_state_save($patch + ['last_run' => time()]);
            backup_log("backup ($reason): " . $res['status'] . (isset($res['error']) ? ' — ' . $res['error'] : '') . (isset($res['commit']) ? ' ' . substr($res['commit'], 0, 7) : ''));
            return $res;
        } finally {
            @unlink($tmp);
        }
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}

// ---------- restore ----------
/** Put a validated SQLite file in place of the live database (atomic). */
function backup_install_db(string $file): void
{
    if (!sqlite_valid($file)) throw new RuntimeException('The backup database failed its integrity check.');
    $live = backup_db_path();
    if (!is_dir(dirname($live))) @mkdir(dirname($live), 0775, true);
    if (is_file($live)) @copy($live, backup_dir() . '/before-restore-' . date('Ymd-His') . '.sqlite');
    $tmp = $live . '.restore-' . bin2hex(random_bytes(3));
    if (!copy($file, $tmp)) throw new RuntimeException('Could not write the database file.');
    foreach (['-wal', '-shm', '-journal'] as $sfx) @unlink($live . $sfx);
    if (!rename($tmp, $live)) throw new RuntimeException('Could not replace the database file.');
}

function backup_restore_github(): array
{
    if (is_mysql()) throw new RuntimeException('Automatic restore is for SQLite. For MySQL, import backup/database.sql in phpMyAdmin.');
    if (!github_backup_enabled()) throw new RuntimeException('GitHub backup is not configured.');
    $remote = gh_backup_tree();
    if (!$remote || !isset($remote['files']['backup/database.sqlite'])) return ['status' => 'none'];
    $tmp = ROOT . '/storage/restore-' . bin2hex(random_bytes(4)) . '.sqlite';
    file_put_contents($tmp, gh_blob($remote['files']['backup/database.sqlite']));
    try {
        backup_install_db($tmp);
    } finally {
        @unlink($tmp);
    }
    $n = 0;
    foreach ($remote['files'] as $path => $sha) {
        if (preg_match('~^backup/uploads/(.+)$~', $path, $m)) $dest = ROOT . '/uploads/' . $m[1];
        elseif (preg_match('~^backup/proofs/(.+)$~', $path, $m)) $dest = ROOT . '/storage/proofs/' . $m[1];
        else continue;
        if (str_contains($m[1], '..') || !preg_match('~\.(jpe?g|png|webp|pdf)$~i', $dest)) continue;
        if (is_file($dest) && git_blob_sha((string) file_get_contents($dest)) === $sha) continue;
        if (!is_dir(dirname($dest))) @mkdir(dirname($dest), 0775, true);
        file_put_contents($dest, gh_blob($sha));
        $n++;
    }
    $meta = sqlite_meta(backup_db_path());
    backup_state_save(['fingerprint' => '', 'last_restore_at' => time(), 'last_restore_from' => 'github ' . substr($remote['commit'], 0, 7), 'last_error' => '', 'lineage_conflict' => false]);
    backup_log('restored from GitHub ' . substr($remote['commit'], 0, 7) . " ({$meta['orders']} orders, $n files)");
    return ['status' => 'restored', 'commit' => $remote['commit'], 'files' => $n, 'orders' => $meta['orders'], 'date' => $remote['date']];
}

function backup_latest_local(): ?string
{
    $all = glob(backup_dir() . '/db-*.sqlite') ?: [];
    rsort($all);
    foreach ($all as $f) if (sqlite_valid($f)) return $f;
    return null;
}

/** Called at server start, before the website runs. Restores only when no database exists. */
function backup_restore_if_missing(): array
{
    if (is_mysql()) return ['status' => 'skip', 'reason' => 'mysql'];
    $live = backup_db_path();
    if (is_file($live) && filesize($live) > 0 && sqlite_valid($live)) return ['status' => 'present'];
    if (github_backup_enabled()) {
        $last = null;
        for ($i = 1; $i <= 4; $i++) {
            try {
                $r = backup_restore_github();
                if ($r['status'] === 'restored') return $r;
                break; // no backup on GitHub yet
            } catch (Throwable $e) {
                $last = $e->getMessage();
                backup_log("restore attempt $i failed: $last");
                sleep(min(10, 2 * $i));
            }
        }
        if ($last) backup_state_save(['last_error' => 'Startup restore failed: ' . $last, 'restore_failed' => true]);
    }
    if ($f = backup_latest_local()) {
        backup_install_db($f);
        backup_log('restored from local backup ' . basename($f));
        return ['status' => 'restored', 'from' => basename($f)];
    }
    return ['status' => 'fresh'];
}
