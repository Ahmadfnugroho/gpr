#!/usr/bin/env bash
set -euo pipefail
IFS=$'\n\t'

# ---------- CONFIG ----------
ENV_FILE="/var/www/gpr/.env"
STAGING="/var/backups/gpr-staging"
OUTDIR="/var/backups"
RCLONE_REMOTE="backup:backups/gpr"
LOGFILE="/var/log/backup.log"
TIMEZONE="Asia/Jakarta"
RETENTION_DAYS=7
LARAVEL_PATH="/var/www/gpr"
NGINX_SITES_AVAILABLE="/etc/nginx/sites-available"
NGINX_SITES_ENABLED="/etc/nginx/sites-enabled"
# Kubernetes PVCs (k3s local-path provisioner). Uploads and WhatsApp
# sessions now live here; /var/www/gpr/storage no longer receives uploads.
K8S_STORAGE="/var/lib/rancher/k3s/storage"
PVC_PROD_STORAGE="${K8S_STORAGE}/pvc-416854fe-9fdb-46b7-9d65-9bd5a8cb2496_gpr-prod_gpr-storage"
PVC_PROD_WAHA="${K8S_STORAGE}/pvc-cf699a51-143d-463c-b63b-4fbc9a9df1c9_gpr-prod_waha-sessions"
PVC_DEV_STORAGE="${K8S_STORAGE}/pvc-909bddf0-c65b-45c9-a29c-27aab11d0d45_gpr-dev_gpr-storage"
PVC_DEV_WAHA="${K8S_STORAGE}/pvc-5fca0a72-b6b0-4dd7-b79f-f2c60b29c450_gpr-dev_waha-sessions"
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

# ---------- Stage Kubernetes PVC data + nginx configs ----------
log "Staging Kubernetes PVC data..."
rm -rf "$STAGING/pvc" "$STAGING/nginx"
mkdir -p "$STAGING/pvc" "$STAGING/nginx"

stage_pvc() {
  local src="$1" name="$2"
  if [ -d "$src" ]; then
    tar -C "$src" -czf "$STAGING/pvc/${name}.tar.gz" . && \
      log "  staged ${name} ($(du -sh "$src" | cut -f1))"
  else
    log "  WARN missing PVC path for ${name}: ${src}"
  fi
}

stage_pvc "$PVC_PROD_STORAGE" "gpr-prod-storage"
stage_pvc "$PVC_PROD_WAHA" "gpr-prod-waha-sessions"
stage_pvc "$PVC_DEV_STORAGE" "gpr-dev-storage"
stage_pvc "$PVC_DEV_WAHA" "gpr-dev-waha-sessions"

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
rm -rf "$STAGING"

# ---------- Local retention: keep last 7 archives ----------
ls -1t "$OUTDIR"/backup-*.tar.gz 2>/dev/null | tail -n +$((RETENTION_DAYS + 1)) | xargs -r rm -f

# ---------- Retention: delete backups older than 7 days ----------
log "Removing old backups on Google Drive (older than $RETENTION_DAYS days)..."
rclone delete "$RCLONE_REMOTE" --min-age "${RETENTION_DAYS}d"

# ---------- Clear Laravel logs ----------
log "Clearing Laravel logs..."
find "${LARAVEL_PATH}/storage/logs" -type f -name "*.log" -exec truncate -s 0 {} \;

log "Backup process completed successfully."