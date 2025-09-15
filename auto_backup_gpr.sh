#!/usr/bin/env bash
set -euo pipefail
IFS=$'\n\t'

# ---------- CONFIG ----------
ENV_FILE="/var/www/gpr/.env"
STAGING="/tmp/backup_staging"
OUTDIR="/var/backups"
RCLONE_REMOTE="backup:backups/gpr"
LOGFILE="/var/log/backup.log"
TIMEZONE="Asia/Jakarta"
RETENTION_DAYS=7
LARAVEL_PATH="/var/www/gpr"
REACT_PATH="/var/www/fegpr"
WA_PATH="/root/waha"
NGINX_SITES_AVAILABLE="/etc/nginx/sites-available"
NGINX_SITES_ENABLED="/etc/nginx/sites-enabled"
# -----------------------------

log() { echo "[$(TZ=$TIMEZONE date '+%Y-%m-%d %H:%M:%S %Z')] $*" | tee -a "$LOGFILE"; }

mkdir -p "$STAGING" "$OUTDIR"
touch "$LOGFILE"

# ---------- Parse DB credentials ----------
get_env() {
  local key="$1" file="$2"
  local val=$(grep -m1 -E "^${key}=" "$file" || true)
  val="${val#${key}=}"; val="${val%\"}"; val="${val#\"}"
  val="${val%\'}"; val="${val#\'}"
  echo "$val"
}

DB_HOST=$(get_env "DB_HOST" "$ENV_FILE"); DB_HOST=${DB_HOST:-127.0.0.1}
DB_PORT=$(get_env "DB_PORT" "$ENV_FILE"); DB_PORT=${DB_PORT:-3306}
DB_NAME=$(get_env "DB_DATABASE" "$ENV_FILE")
DB_USER=$(get_env "DB_USERNAME" "$ENV_FILE")
DB_PASS=$(get_env "DB_PASSWORD" "$ENV_FILE")

DATE=$(TZ=$TIMEZONE date +%F)
SQL_FILE="db-${DATE}.sql"
BACKUP_NAME="backup-${DATE}.tar.gz"

log "Dumping MySQL database..."
export MYSQL_PWD="$DB_PASS"
mysqldump --single-transaction --quick --lock-tables=false --no-tablespaces \
    -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" "$DB_NAME" > "$STAGING/$SQL_FILE"
unset MYSQL_PWD

# ---------- Copy application files to staging ----------
log "Copying application files to staging..."
rm -rf "$STAGING/app" "$STAGING/frontend" "$STAGING/waha" "$STAGING/nginx"
mkdir -p "$STAGING/app" "$STAGING/frontend" "$STAGING/waha" "$STAGING/nginx"

cp -r "$LARAVEL_PATH"/* "$STAGING/app/"
cp -r "$REACT_PATH"/* "$STAGING/frontend/"
cp -r "$WA_PATH"/* "$STAGING/waha/"
cp -r "$NGINX_SITES_AVAILABLE" "$STAGING/nginx/sites-available"
cp -r "$NGINX_SITES_ENABLED" "$STAGING/nginx/sites-enabled"

# ---------- Create tar.gz ----------
log "Creating compressed backup archive..."
tar -C "$STAGING" -czf "$OUTDIR/$BACKUP_NAME" .

# ---------- Upload to Google Drive ----------
log "Uploading backup to Google Drive..."
rclone copy "$OUTDIR/$BACKUP_NAME" "$RCLONE_REMOTE" \
    --drive-chunk-size 64M --transfers 4 --checkers 8 --verbose

# ---------- Delete local backup ----------
log "Deleting local backup file..."
rm -f "$OUTDIR/$BACKUP_NAME"

# ---------- Retention: delete backups older than 7 days ----------
log "Removing old backups on Google Drive (older than $RETENTION_DAYS days)..."
rclone delete "$RCLONE_REMOTE" --min-age "${RETENTION_DAYS}d"

# ---------- Clear Laravel logs ----------
log "Clearing Laravel logs..."
find "${LARAVEL_PATH}/storage/logs" -type f -name "*.log" -exec truncate -s 0 {} \;

log "Backup process completed successfully."
