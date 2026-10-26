<?php
require_once __DIR__ . '/_init.php';
$me = require_admin();
@set_time_limit(300);

// ---- downloads ----
$dl = qin('download');
if ($dl === 'full') {
    if (!class_exists('ZipArchive')) { flash('error', 'Zip support is not available on this server — download the database file instead.'); redirect(url('admin/backups.php')); }
    [$tmp, $ext] = backup_snapshot();
    $zipFile = ROOT . '/storage/full-backup-' . bin2hex(random_bytes(4)) . '.zip';
    $z = new ZipArchive();
    $z->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    foreach (backup_files($tmp, $ext) as $path => $abs) $z->addFile($abs, $path);
    $z->close();
    @unlink($tmp);
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="mojhometouch-backup-' . date('Ymd-His') . '.zip"');
    header('Content-Length: ' . filesize($zipFile));
    readfile($zipFile);
    @unlink($zipFile);
    exit;
}
if ($dl !== '') {
    $f = backup_dir() . '/' . basename($dl);
    if (!preg_match('~^(db|before-restore)-[\d-]+\.(sqlite|sql)$~', basename($dl)) || !is_file($f)) { http_response_code(404); exit('Not found'); }
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="mojhometouch-' . basename($f) . '"');
    header('Content-Length: ' . filesize($f));
    readfile($f);
    exit;
}

// ---- actions ----
if (is_post()) {
    csrf_check();
    $act = pin('action');
    try {
        if ($act === 'backup') {
            $r = backup_run(true, pin('overwrite') === '1', 'manual by ' . $me['name']);
            if ($r['status'] === 'error') throw new RuntimeException($r['error']);
            if ($r['status'] === 'busy') throw new RuntimeException('A backup is already running. Try again in a moment.');
            flash('success', match ($r['status']) {
                'pushed' => 'Backup saved and pushed to GitHub (' . substr($r['commit'], 0, 7) . ').',
                'unchanged' => 'Backup saved. GitHub already had the latest copy.',
                default => 'Backup saved on the server (' . $r['local'] . ').',
            });
        } elseif ($act === 'restore_github') {
            if (pin('confirm') !== 'RESTORE') throw new RuntimeException('Type RESTORE to confirm.');
            $r = backup_restore_github();
            if ($r['status'] !== 'restored') throw new RuntimeException('No backup was found on GitHub.');
            backup_state_save(['restore_failed' => false]);
            flash('success', 'Restored from GitHub backup ' . substr($r['commit'], 0, 7) . ' — ' . $r['orders'] . ' orders. Please sign in again if asked.');
        } elseif ($act === 'restore_local') {
            if (pin('confirm') !== 'RESTORE') throw new RuntimeException('Type RESTORE to confirm.');
            $f = backup_dir() . '/' . basename(pin('file'));
            if (!preg_match('~^(db|before-restore)-[\d-]+\.sqlite$~', basename($f)) || !is_file($f)) throw new RuntimeException('Backup file not found.');
            backup_install_db($f);
            backup_state_save(['fingerprint' => '', 'last_restore_at' => time(), 'last_restore_from' => basename($f), 'lineage_conflict' => false, 'last_error' => '']);
            flash('success', 'Database restored from ' . basename($f) . '.');
        } elseif ($act === 'restore_upload') {
            if (pin('confirm') !== 'RESTORE') throw new RuntimeException('Type RESTORE to confirm.');
            if (!has_upload('dbfile') || $_FILES['dbfile']['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('Choose a .sqlite backup file.');
            backup_install_db($_FILES['dbfile']['tmp_name']);
            backup_state_save(['fingerprint' => '', 'last_restore_at' => time(), 'last_restore_from' => 'uploaded file', 'lineage_conflict' => false, 'last_error' => '']);
            flash('success', 'Database restored from the uploaded file.');
        }
    } catch (Throwable $e) {
        flash('error', preg_replace('~^LINEAGE: ~', '', $e->getMessage()));
    }
    redirect(url('admin/backups.php'));
}

$st = backup_state();
$cfg = backup_cfg();
$gh = github_backup_enabled();
$locals = glob(backup_dir() . '/{db,before-restore}-*.{sqlite,sql}', GLOB_BRACE) ?: [];
usort($locals, fn($a, $b) => filemtime($b) <=> filemtime($a));
$ago = function (?int $t): string {
    if (!$t) return 'never';
    $d = time() - $t;
    return $d < 90 ? 'just now' : ($d < 5400 ? round($d / 60) . ' min ago' : ($d < 172800 ? round($d / 3600) . ' hours ago' : date('j M Y, g:ia', $t)));
};
admin_header('Backups', 'backups');
?>
<?php if (!empty($st['lineage_conflict'])): ?>
<div class="alert alert-error"><?= ic('alert') ?><div><b>This database is different from your GitHub backup.</b> Automatic backups are paused so your backup isn't overwritten. This usually means the server restarted and couldn't load the backup.
  <b>Normally you should “Restore from GitHub” below.</b> Only choose “Overwrite GitHub backup” if you are sure this database is the correct one.</div></div>
<?php elseif (!empty($st['last_error'])): ?>
<div class="alert alert-error"><?= ic('alert') ?><div><b>Last backup problem:</b> <?= e($st['last_error']) ?></div></div>
<?php endif; ?>
<?php if (!$gh && getenv('RENDER')): ?>
<div class="alert alert-warn"><?= ic('alert') ?><div><b>GitHub backup is not set up.</b> On Render, files can be wiped on every deploy or restart. Add <code>GITHUB_BACKUP_TOKEN</code> and <code>GITHUB_BACKUP_REPO</code> in Render → Environment so your orders and products are kept safe.</div></div>
<?php endif; ?>

<div class="stats">
  <div class="stat"><span>Last backup</span><b style="font-size:1.25rem"><?= e($ago($st['last_run'] ?? null)) ?></b><small><?= e($st['last_local'] ?? 'No backup yet') ?></small></div>
  <div class="stat <?= $gh ? '' : 'warn' ?>"><span>GitHub backup</span><b style="font-size:1.25rem"><?= $gh ? e($ago($st['last_push_at'] ?? null)) : 'Not set up' ?></b><small><?= $gh ? e($cfg['github_repo'] . ' → ' . $cfg['github_branch']) : 'Add environment variables' ?></small></div>
  <div class="stat"><span>Server copies</span><b><?= count($locals) ?></b><small>Newest <?= (int) $cfg['keep_local'] ?> kept in /backups</small></div>
  <div class="stat"><span>Automatic</span><b style="font-size:1.25rem"><?= is_file(ROOT . '/storage/backup-loop.pid') || getenv('RENDER') ? 'On' : 'Cron needed' ?></b><small>Every change, at most every <?= max(1, round($cfg['min_gap'] / 60)) ?> min</small></div>
</div>

<div class="cols">
  <div class="card">
    <div class="card-h"><h2><?= ic('shield') ?> Back up now</h2></div>
    <p class="hint">Backups run automatically after changes. Use this before big edits, or to download a copy for your own records.</p>
    <form method="post" style="display:flex;gap:8px;flex-wrap:wrap;margin-top:14px"><?= csrf_field() ?><input type="hidden" name="action" value="backup">
      <button class="btn btn-primary" type="submit"><?= ic('refresh') ?> Back up now</button>
      <a class="btn btn-line" href="?download=full"><?= ic('file') ?> Download full backup (.zip)</a>
    </form>
    <?php if (!empty($st['lineage_conflict'])): ?>
    <form method="post" style="margin-top:14px" onsubmit="return confirm('Replace the GitHub backup with THIS database? The old backup stays in the branch history.')"><?= csrf_field() ?><input type="hidden" name="action" value="backup"><input type="hidden" name="overwrite" value="1"><button class="btn btn-sm btn-danger" type="submit">Overwrite GitHub backup with this database</button></form>
    <?php endif; ?>
    <?php if ($gh && !empty($st['last_commit'])): ?><p class="hint" style="margin-top:14px">Latest GitHub restore point: <a class="link" href="https://github.com/<?= e($cfg['github_repo']) ?>/commit/<?= e($st['last_commit']) ?>" target="_blank" rel="noopener"><?= e(substr($st['last_commit'], 0, 7)) ?> <?= ic('external') ?></a> · <a class="link" href="https://github.com/<?= e($cfg['github_repo']) ?>/commits/<?= e($cfg['github_branch']) ?>" target="_blank" rel="noopener">all restore points</a></p><?php endif; ?>
  </div>

  <div class="card">
    <div class="card-h"><h2><?= ic('refresh') ?> Restore</h2></div>
    <p class="hint">Restoring replaces the live database. A safety copy of the current database is saved first.</p>
    <?php if ($gh): ?>
    <form method="post" class="form-grid" style="margin-top:14px"><?= csrf_field() ?><input type="hidden" name="action" value="restore_github">
      <label class="f"><span>Type RESTORE to confirm</span><input name="confirm" autocomplete="off"></label>
      <div class="f" style="align-content:end"><button class="btn btn-dark" type="submit">Restore latest from GitHub</button></div>
    </form>
    <?php endif; ?>
    <form method="post" enctype="multipart/form-data" class="form-grid" style="margin-top:10px"><?= csrf_field() ?><input type="hidden" name="action" value="restore_upload">
      <label class="f"><span>Upload a .sqlite backup</span><input type="file" name="dbfile" accept=".sqlite,application/octet-stream"></label>
      <label class="f"><span>Type RESTORE to confirm</span><input name="confirm" autocomplete="off"></label>
      <div class="full"><button class="btn btn-line" type="submit"><?= ic('upload') ?> Restore uploaded file</button></div>
    </form>
  </div>
</div>

<div class="card flush">
  <div class="card-h" style="padding:18px 22px 0"><h2>Backups on this server</h2></div>
  <?php if ($locals): ?>
  <div class="tbl-wrap"><table class="tbl">
    <thead><tr><th>File</th><th>Date</th><th>Size</th><th></th></tr></thead>
    <tbody><?php foreach ($locals as $f): $b = basename($f); ?>
      <tr><td><b><?= e($b) ?></b><?= str_starts_with($b, 'before-restore') ? '<small>Safety copy taken before a restore</small>' : '' ?></td><td><?= date('j M Y, g:ia', filemtime($f)) ?></td><td><?= number_format(filesize($f) / 1024, 0) ?> KB</td>
      <td class="acts"><a class="btn btn-sm btn-line" href="?download=<?= e($b) ?>"><?= ic('file') ?> Download</a>
        <?php if (str_ends_with($b, '.sqlite')): ?><form method="post" onsubmit="var c=prompt('Type RESTORE to replace the live database with <?= e($b) ?>');if(c!=='RESTORE')return false;this.confirm.value=c;"><?= csrf_field() ?><input type="hidden" name="action" value="restore_local"><input type="hidden" name="file" value="<?= e($b) ?>"><input type="hidden" name="confirm"><button class="btn btn-sm btn-line" type="submit">Restore</button></form><?php endif; ?></td></tr>
    <?php endforeach; ?></tbody>
  </table></div>
  <?php else: ?><div class="empty-s"><?= ic('shield') ?><p>No backups yet — click “Back up now”.</p></div><?php endif; ?>
</div>

<div class="card">
  <div class="card-h"><h2>How backups work</h2></div>
  <ul class="mini-list">
    <li><span><b>Automatic</b><small>After any change (orders, products, settings, uploads) a backup is made within a few minutes, and once more when the server shuts down for a deploy.</small></span></li>
    <li><span><b>GitHub folder</b><small>The database, product images and payment receipts are saved to the <code>backup/</code> folder on the <code><?= e($cfg['github_branch']) ?></code> branch of your private repository. Each backup is a commit you can go back to.</small></span></li>
    <li><span><b>Auto-restore</b><small>If the server starts without a database (new deploy, restart, lost disk), the latest backup is restored before the site opens.</small></span></li>
    <li><span><b>Safe</b><small>Backups are never sent to a public repository, and a different/empty database never overwrites your GitHub backup automatically.</small></span></li>
  </ul>
</div>
<?php admin_footer();
