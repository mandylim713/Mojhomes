#!/usr/bin/env bash
# Mojhometouch container start-up:
#  1. Listen on $PORT (Render / Railway compatible)
#  2. Keep data on the persistent disk ($DATA_DIR) when one is attached
#  3. Seed DB from docker/seed.sql if no database exists (first deploy)
#  4. Restore the latest backup if there is no database (new deploy / restart)
#  5. Back up automatically every minute (only when something changed)
#  6. Take a final backup when the container stops
set -u
APP=${APP_DIR:-/var/www/html}
PORT="${PORT:-10000}"
PHP_USER=${PHP_USER:-www-data}
as_app() { if [ "$(id -u)" = "0" ]; then runuser -u "$PHP_USER" -- "$@"; else "$@"; fi; }

if [ -f /etc/apache2/ports.conf ]; then
  sed -ri "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
  sed -ri "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf
fi

if [ -n "${DATA_DIR:-}" ]; then
  mkdir -p "$DATA_DIR"
  for d in storage uploads backups; do
    mkdir -p "$DATA_DIR/$d"
    if [ ! -L "$APP/$d" ]; then
      cp -an "$APP/$d/." "$DATA_DIR/$d/" 2>/dev/null || true   # first run: seed .htaccess/.keep and any committed backups
      rm -rf "${APP:?}/$d"
      ln -s "$DATA_DIR/$d" "$APP/$d"
    fi
  done
fi
mkdir -p "$APP/storage/logs" "$APP/storage/proofs" "$APP/uploads/products" "$APP/uploads/gallery" "$APP/uploads/categories" "$APP/backups"
[ "$(id -u)" = "0" ] && chown -R "$PHP_USER:$PHP_USER" "$APP/storage/" "$APP/uploads/" "$APP/backups/"

echo "[moj] checking database / restoring backup if needed…"
as_app php "$APP/cli/backup.php" restore-if-missing || echo "[moj] restore step reported a problem — see Admin → Backups"

# --- Seed from committed SQL if DB still missing (Railway first deploy / no GitHub token) ---
DB_FILE="${DATA_DIR:-$APP/storage}/mojhometouch.sqlite"
# also check the symlinked path
if [ ! -f "$APP/storage/mojhometouch.sqlite" ] && [ -f "$APP/docker/seed.sql" ]; then
  echo "[moj] no database found — seeding from docker/seed.sql…"
  as_app sqlite3 "$APP/storage/mojhometouch.sqlite" < "$APP/docker/seed.sql" && echo "[moj] seed complete" || echo "[moj] seed failed"
fi

APP_DIR="$APP" as_app php -r 'require (getenv("APP_DIR") ?: "/var/www/html") . "/includes/bootstrap.php"; db(); echo "[moj] database ready\n";'
if [ -f "$APP/scripts/update_products.php" ]; then
  APP_DIR="$APP" as_app php "$APP/scripts/update_products.php" || true
fi

( while true; do
    sleep "${BACKUP_LOOP_SECONDS:-60}"
    as_app php "$APP/cli/backup.php" auto >/dev/null 2>&1 || true
  done ) &
LOOP_PID=$!
echo "$LOOP_PID" > "$APP/storage/backup-loop.pid"

shutdown() {
  echo "[moj] stopping — taking final backup…"
  kill "$LOOP_PID" 2>/dev/null || true
  as_app php "$APP/cli/backup.php" shutdown || true
  if [ -n "${SERVER_PID:-}" ]; then kill -TERM "$SERVER_PID" 2>/dev/null || true; wait "$SERVER_PID" 2>/dev/null || true; fi
  rm -f "$APP/storage/backup-loop.pid"
  echo "[moj] stopped"
  exit 0
}
trap shutdown TERM INT

# Ensure only mpm_prefork is active (fixes AH00534: More than one MPM loaded)
rm -f /etc/apache2/mods-enabled/mpm_event.* /etc/apache2/mods-enabled/mpm_worker.* 2>/dev/null || true
a2enmod mpm_prefork 2>/dev/null || true

if [ -n "${SERVER_CMD:-}" ]; then
  bash -c "$SERVER_CMD" &          # used for local testing
else
  apache2-foreground &
fi
SERVER_PID=$!
wait "$SERVER_PID"
