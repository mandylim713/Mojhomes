<?php
/**
 * Backup command line tool (run by the server automatically — see docker/entrypoint.sh)
 *   php cli/backup.php auto                 back up if something changed (used every minute)
 *   php cli/backup.php now                  back up immediately
 *   php cli/backup.php restore-if-missing   restore latest backup when no database exists (server start)
 *   php cli/backup.php restore              restore the latest GitHub backup (replaces the live database)
 *   php cli/backup.php status
 * cPanel cron example (every 10 minutes):  php /home/USER/public_html/cli/backup.php auto
 */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/includes/bootstrap.php';

$cmd = $argv[1] ?? 'auto';
try {
    switch ($cmd) {
        case 'restore-if-missing':
            $r = backup_restore_if_missing();
            break;
        case 'restore':
            $r = backup_restore_github();
            break;
        case 'now':
            $r = backup_run(true, in_array('--overwrite', $argv, true), 'manual');
            break;
        case 'shutdown':
            $r = backup_run(true, false, 'shutdown');
            break;
        case 'status':
            $r = backup_state() + ['github' => github_backup_enabled() ? backup_cfg()['github_repo'] . '@' . backup_cfg()['github_branch'] : 'not configured'];
            break;
        case 'auto':
        default:
            $st = backup_state();
            // Self-heal: if the startup restore failed (e.g. GitHub briefly unreachable) and no orders
            // have been taken on the fresh database yet, try the restore again.
            if (!empty($st['restore_failed']) && github_backup_enabled() && !is_mysql()) {
                if ((int) q_val('SELECT COUNT(*) FROM orders') === 0) {
                    try {
                        $rr = backup_restore_github();
                        backup_state_save(['restore_failed' => false]);
                        echo json_encode(['retry_restore' => $rr]), PHP_EOL;
                        exit(0);
                    } catch (Throwable $e) {
                        backup_log('retry restore failed: ' . $e->getMessage());
                    }
                } else {
                    backup_state_save(['restore_failed' => false]);
                }
            }
            $r = backup_run(false, false, 'auto');
    }
    echo json_encode($r, JSON_UNESCAPED_SLASHES), PHP_EOL;
    exit(($r['status'] ?? '') === 'error' ? 1 : 0);
} catch (Throwable $e) {
    backup_log("$cmd failed: " . $e->getMessage());
    fwrite(STDERR, $e->getMessage() . PHP_EOL);
    exit(1);
}
