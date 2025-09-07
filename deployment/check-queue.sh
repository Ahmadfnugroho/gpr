#!/bin/bash

# Script untuk mengecek apakah queue worker masih berjalan
# Jika tidak, restart worker

LARAVEL_PATH="/path/to/your/laravel"
LOCK_FILE="$LARAVEL_PATH/storage/logs/queue-worker.lock"
LOG_FILE="$LARAVEL_PATH/storage/logs/queue-monitor.log"

# Function untuk log dengan timestamp
log_message() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') - $1" >> "$LOG_FILE"
}

# Check apakah lock file ada dan masih fresh (kurang dari 10 menit)
if [ -f "$LOCK_FILE" ]; then
    # Check apakah file lock masih fresh (modified dalam 10 menit terakhir)
    if [ $(find "$LOCK_FILE" -mmin -10 | wc -l) -eq 1 ]; then
        log_message "Queue worker masih berjalan"
        exit 0
    fi
fi

log_message "Queue worker tidak terdeteksi, memulai ulang..."

# Kill existing queue processes jika ada
pkill -f "artisan queue:work"

# Start queue worker baru dalam background
cd "$LARAVEL_PATH"
nohup php artisan queue:work --sleep=3 --tries=3 --max-time=3600 > /dev/null 2>&1 &

# Create lock file
touch "$LOCK_FILE"

log_message "Queue worker telah dimulai ulang"

# Make this script executable:
# chmod +x check-queue.sh
