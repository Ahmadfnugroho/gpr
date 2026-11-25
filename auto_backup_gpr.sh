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
# Paths are resolved by namespace+PVC name because the directory prefix is the
# PVC uid, which changes whenever a PVC is recreated.
K8S_STORAGE="/var/lib/rancher/k3s/storage"
pvc_path() {
  ls -d "${K8S_STORAGE}"/pvc-*_"$1"_"$2" 2>/dev/null | head -1
}
# -----------------------------

log() { echo "[$(TZ=$TIMEZONE date '+%Y-%m-%d %H:%M:%S %Z')] $*" | tee -a "$LOGFILE"; }

# Anything that leaves half-written staging behind (ENOSPC, kubectl pipe death,
# SIGTERM from cron) must not leak. This is what produced the 196k "No space
# left on device" lines and the stale /tmp/backup_staging in the first place.
cleanup() { rm -rf "$STAGING" /tmp/backup_staging; }
trap cleanup EXIT INT TERM

mkdir -p "$STAGING" "$OUTDIR"
touch "$LOGFILE"

# Refuse to run into a full disk: a half backup is worse than none.
AVAIL_KB=$(df --output=avail "$OUTDIR" | tail -1 | tr -d ' ')
if [ "$AVAIL_KB" -lt 5242880 ]; then
  log "ABORT: only $((AVAIL_KB / 1024 / 1024))GiB free on $OUTDIR (need 5GiB)"
  exit 1
fi

DATE=$(TZ=$TIMEZONE date +%F)
BACKUP_NAME="backup-${DATE}.tar.gz"

# ---------- Dump the databases the cluster actually uses ----------
# ENV_FILE (/var/www/gpr/.env) belongs to the retired VM app (db "gpr").
# The namespaces talk to gpr_prod / gpr_dev with their own users, read
# from the gpr-secrets secret. Keep ENV_FILE only as DB_HOST source.
get_env() {
  local key="$1" file="$2"
  local val=$(grep -m1 -E "^${key}=" "$file" || true)
  val="${val#${key}=}"; val="${val%\"}"; val="${val#\"}"
  val="${val%\'}"; val="${val#\'}"
  echo "$val"
}
DB_HOST=$(get_env "DB_HOST" "$ENV_FILE"); DB_HOST=${DB_HOST:-127.0.0.1}
DB_PORT=$(get_env "DB_PORT" "$ENV_FILE"); DB_PORT=${DB_PORT:-3306}

dump_ns_db() {
  local ns="$1" secret="gpr-secrets"
  local u p d out
  u=$(kubectl -n "$ns" get secret "$secret" -o jsonpath='{.data.DB_USERNAME}' | base64 -d)
  p=$(kubectl -n "$ns" get secret "$secret" -o jsonpath='{.data.DB_PASSWORD}' | base64 -d)
  d=$(kubectl -n "$ns" get secret "$secret" -o jsonpath='{.data.DB_DATABASE}' | base64 -d)
  out="$STAGING/db-${d}-${DATE}.sql"
  log "Dumping MySQL ${d} (namespace ${ns})..."
  MYSQL_PWD="$p" mysqldump --single-transaction --quick --lock-tables=false --no-tablespaces \
      -h "$DB_HOST" -P "$DB_PORT" -u "$u" "$d" > "$out"
  log "  dumped ${d} ($(du -h "$out" | cut -f1))"
}

dump_ns_db gpr-prod
dump_ns_db gpr-dev

# ---------- Stage Kubernetes PVC data + nginx configs ----------
log "Staging Kubernetes PVC data..."
rm -rf "$STAGING/pvc" "$STAGING/nginx"
mkdir -p "$STAGING/pvc" "$STAGING/nginx"

stage_pvc() {
  local src="$1" name="$2"
  if [ -n "$src" ] && [ -d "$src" ]; then
    tar -C "$src" -czf "$STAGING/pvc/${name}.tar.gz" . && \
      log "  staged ${name} ($(du -sh "$src" | cut -f1))"
  else
    log "  WARN missing PVC path for ${name}: ${src:-<not found>}"
  fi
}

# Media lives in the MinIO buckets on these PVCs. The old gpr-storage PVCs still
# hold framework caches and logs only, so they are no longer archived.
stage_pvc "$(pvc_path gpr-prod minio-data)" "gpr-prod-minio-data"
stage_pvc "$(pvc_path gpr-prod waha-sessions)" "gpr-prod-waha-sessions"
stage_pvc "$(pvc_path gpr-dev minio-data)" "gpr-dev-minio-data"
stage_pvc "$(pvc_path gpr-dev waha-sessions)" "gpr-dev-waha-sessions"

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

# ---------- Local retention by AGE ----------
# Counting files is useless here: the archive we just made was deleted two lines
# up, so `ls -1t | tail -n +8` never had a 8th entry to remove. Age is the only
# signal that actually reflects what is stale.
find "$OUTDIR" -maxdepth 1 -name 'backup-*.tar.gz' -mtime +"$RETENTION_DAYS" -print -delete

# ---------- Retention: delete backups older than 7 days ----------
log "Removing old backups on Google Drive (older than $RETENTION_DAYS days)..."
rclone delete "$RCLONE_REMOTE" --min-age "${RETENTION_DAYS}d"

# ---------- Clear Laravel logs ----------
log "Clearing Laravel logs..."
find "${LARAVEL_PATH}/storage/logs" -type f -name "*.log" -exec truncate -s 0 {} \;

log "Backup process completed successfully."