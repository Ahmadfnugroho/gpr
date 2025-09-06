#!/bin/bash

# =============================================================================
# 🚀 GLOBAL PHOTO RENTAL - PRODUCTION DEPLOYMENT SCRIPT
# =============================================================================
# 
# USAGE: ./deploy-production.sh [options]
# OPTIONS:
#   --skip-backup      Skip backup process
#   --skip-mysql       Skip MySQL optimization
#   --skip-php         Skip PHP optimization
#   --skip-supervisor  Skip supervisor setup
#   --dry-run         Show what would be done without executing
#
# =============================================================================

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
APP_PATH="$(pwd)"
BACKUP_DIR="$APP_PATH/backups"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
DRY_RUN=false

# Parse command line arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        --skip-backup)
            SKIP_BACKUP=true
            shift
            ;;
        --skip-mysql)
            SKIP_MYSQL=true
            shift
            ;;
        --skip-php)
            SKIP_PHP=true
            shift
            ;;
        --skip-supervisor)
            SKIP_SUPERVISOR=true
            shift
            ;;
        --dry-run)
            DRY_RUN=true
            shift
            ;;
        *)
            echo "Unknown option: $1"
            exit 1
            ;;
    esac
done

# Helper functions
log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

execute_or_dry_run() {
    if [ "$DRY_RUN" = true ]; then
        log_info "[DRY RUN] Would execute: $1"
    else
        log_info "Executing: $1"
        eval "$1"
    fi
}

check_root() {
    if [[ $EUID -ne 0 ]]; then
        log_error "This script must be run as root (use sudo)"
        exit 1
    fi
}

check_dependencies() {
    log_info "Checking dependencies..."
    
    local deps=("mysql" "php" "composer" "supervisorctl" "nginx")
    local missing=()
    
    for dep in "${deps[@]}"; do
        if ! command -v "$dep" &> /dev/null; then
            missing+=("$dep")
        fi
    done
    
    if [ ${#missing[@]} -ne 0 ]; then
        log_error "Missing dependencies: ${missing[*]}"
        log_info "Please install missing dependencies and try again"
        exit 1
    fi
    
    log_success "All dependencies found"
}

create_backup() {
    if [ "$SKIP_BACKUP" = true ]; then
        log_warning "Skipping backup (--skip-backup specified)"
        return
    fi
    
    log_info "Creating backup..."
    
    # Create backup directory
    execute_or_dry_run "mkdir -p $BACKUP_DIR"
    
    # Database backup
    log_info "Backing up database..."
    DB_USER=$(grep DB_USERNAME .env | cut -d '=' -f2 | tr -d '"')
    DB_NAME=$(grep DB_DATABASE .env | cut -d '=' -f2 | tr -d '"')
    
    if [ -z "$DB_USER" ] || [ -z "$DB_NAME" ]; then
        log_error "Could not read database credentials from .env file"
        exit 1
    fi
    
    execute_or_dry_run "mysqldump -u $DB_USER -p $DB_NAME > $BACKUP_DIR/database_$TIMESTAMP.sql"
    
    # Application backup
    log_info "Backing up application..."
    execute_or_dry_run "tar -czf $BACKUP_DIR/app_$TIMESTAMP.tar.gz --exclude='backups' --exclude='storage/logs/*' --exclude='node_modules' ."
    
    # Configuration backup
    log_info "Backing up configurations..."
    execute_or_dry_run "cp /etc/mysql/my.cnf $BACKUP_DIR/mysql_$TIMESTAMP.cnf" || true
    execute_or_dry_run "cp /etc/php/*/apache2/php.ini $BACKUP_DIR/php_$TIMESTAMP.ini" || true
    execute_or_dry_run "cp /etc/php/*/fpm/php.ini $BACKUP_DIR/php-fpm_$TIMESTAMP.ini" || true
    
    log_success "Backup completed at $BACKUP_DIR"
}

optimize_mysql() {
    if [ "$SKIP_MYSQL" = true ]; then
        log_warning "Skipping MySQL optimization (--skip-mysql specified)"
        return
    fi
    
    log_info "Optimizing MySQL configuration..."
    
    # Create optimized MySQL config
    cat > /tmp/mysql_optimization.cnf << 'EOF'
# MySQL Configuration Optimization for Bulk Operations
[mysqld]
# Buffer Settings for Better Bulk Performance
innodb_buffer_pool_size = 1G
innodb_buffer_pool_instances = 4
innodb_log_file_size = 256M
innodb_log_buffer_size = 16M
innodb_flush_log_at_trx_commit = 2

# Connection and Query Cache
max_connections = 300
query_cache_size = 64M
query_cache_type = 1
query_cache_limit = 2M

# Table Settings
table_open_cache = 2000
table_definition_cache = 1000

# Bulk Insert Optimization
bulk_insert_buffer_size = 8M
myisam_sort_buffer_size = 32M
key_buffer_size = 128M

# Transaction Settings
innodb_lock_wait_timeout = 50
innodb_rollback_on_timeout = ON

# Memory Settings
tmp_table_size = 64M
max_heap_table_size = 64M
sort_buffer_size = 2M
read_buffer_size = 1M
read_rnd_buffer_size = 2M
join_buffer_size = 2M

# Timeout Settings
wait_timeout = 600
interactive_timeout = 600
net_read_timeout = 120
net_write_timeout = 120

[mysql]
default-character-set = utf8mb4

[client]
default-character-set = utf8mb4
EOF
    
    # Backup current config and apply new one
    execute_or_dry_run "cp /etc/mysql/my.cnf /etc/mysql/my.cnf.backup-$TIMESTAMP"
    execute_or_dry_run "cat /tmp/mysql_optimization.cnf >> /etc/mysql/my.cnf"
    
    # Restart MySQL
    execute_or_dry_run "systemctl restart mysql"
    
    # Verify MySQL is running
    execute_or_dry_run "systemctl is-active --quiet mysql"
    
    log_success "MySQL optimization completed"
}

optimize_php() {
    if [ "$SKIP_PHP" = true ]; then
        log_warning "Skipping PHP optimization (--skip-php specified)"
        return
    fi
    
    log_info "Optimizing PHP configuration..."
    
    # Find PHP version
    PHP_VERSION=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
    PHP_INI_PATH="/etc/php/$PHP_VERSION"
    
    if [ ! -d "$PHP_INI_PATH" ]; then
        log_error "Could not find PHP configuration directory"
        exit 1
    fi
    
    # Create PHP optimization config
    cat > /tmp/php_optimization.ini << 'EOF'
; PHP Configuration Optimization for Bulk Operations

; Memory Management
memory_limit = 1024M
max_execution_time = 900
max_input_time = 600
max_input_vars = 10000
post_max_size = 256M
upload_max_filesize = 256M

; Performance Settings
realpath_cache_size = 4096K
realpath_cache_ttl = 600

; OPcache Settings
opcache.enable = 1
opcache.enable_cli = 1
opcache.memory_consumption = 256
opcache.interned_strings_buffer = 16
opcache.max_accelerated_files = 20000
opcache.revalidate_freq = 5
opcache.fast_shutdown = 1

; Resource Limits for Bulk Operations
max_input_nesting_level = 128
pcre.backtrack_limit = 1000000
pcre.recursion_limit = 100000
EOF
    
    # Apply to different SAPIs
    for sapi in apache2 fpm cli; do
        if [ -f "$PHP_INI_PATH/$sapi/php.ini" ]; then
            execute_or_dry_run "cp $PHP_INI_PATH/$sapi/php.ini $PHP_INI_PATH/$sapi/php.ini.backup-$TIMESTAMP"
            execute_or_dry_run "cat /tmp/php_optimization.ini >> $PHP_INI_PATH/$sapi/php.ini"
            log_info "Updated PHP $sapi configuration"
        fi
    done
    
    # Restart web server and PHP-FPM
    execute_or_dry_run "systemctl restart apache2" || execute_or_dry_run "systemctl restart nginx"
    execute_or_dry_run "systemctl restart php$PHP_VERSION-fpm" || true
    
    log_success "PHP optimization completed"
}

setup_supervisor() {
    if [ "$SKIP_SUPERVISOR" = true ]; then
        log_warning "Skipping supervisor setup (--skip-supervisor specified)"
        return
    fi
    
    log_info "Setting up Supervisor for queue workers..."
    
    # Install supervisor if not present
    if ! command -v supervisorctl &> /dev/null; then
        execute_or_dry_run "apt-get update && apt-get install -y supervisor"
    fi
    
    # Create supervisor configuration
    cat > /etc/supervisor/conf.d/gpr-worker.conf << EOF
[program:gpr-worker]
process_name=%(program_name)s_%(process_num)02d
command=php $APP_PATH/artisan queue:work --timeout=600 --sleep=3 --tries=3
directory=$APP_PATH
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=$APP_PATH/storage/logs/worker.log
stopwaitsecs=3600
EOF
    
    # Reload supervisor and start workers
    execute_or_dry_run "supervisorctl reread"
    execute_or_dry_run "supervisorctl update"
    execute_or_dry_run "supervisorctl start gpr-worker:*"
    
    log_success "Supervisor setup completed"
}

setup_cron_jobs() {
    log_info "Setting up cron jobs..."
    
    # Create cron file
    cat > /tmp/gpr-cron << EOF
# Laravel Scheduler
* * * * * cd $APP_PATH && php artisan schedule:run >> /dev/null 2>&1

# Queue cleanup (every hour)
0 * * * * cd $APP_PATH && php artisan queue:prune-batches --hours=48 --unfinished=72

# Cache cleanup (daily at 2 AM)
0 2 * * * cd $APP_PATH && php artisan cache:clear

# Log rotation (daily at 3 AM)  
0 3 * * * cd $APP_PATH && find $APP_PATH/storage/logs -name "*.log" -mtime +7 -delete

# Progress cache cleanup (every 6 hours)
0 */6 * * * cd $APP_PATH && php artisan tinker --execute="Cache::forget('bulk_job_progress_*');"
EOF
    
    # Install cron jobs
    execute_or_dry_run "crontab -u www-data /tmp/gpr-cron"
    
    log_success "Cron jobs setup completed"
}

setup_application() {
    log_info "Setting up Laravel application..."
    
    # Install/Update dependencies
    execute_or_dry_run "composer install --optimize-autoloader --no-dev --no-interaction"
    
    # Cache configuration
    execute_or_dry_run "php artisan config:cache"
    execute_or_dry_run "php artisan route:cache"
    execute_or_dry_run "php artisan view:cache"
    
    # Run migrations
    execute_or_dry_run "php artisan migrate --force"
    
    # Seed API keys
    execute_or_dry_run "php artisan db:seed --class=ApiKeySeeder"
    
    # Clear and rebuild cache
    execute_or_dry_run "php artisan cache:clear"
    
    # Set permissions
    execute_or_dry_run "chown -R www-data:www-data storage bootstrap/cache"
    execute_or_dry_run "chmod -R 775 storage bootstrap/cache"
    
    log_success "Application setup completed"
}

run_tests() {
    log_info "Running deployment tests..."
    
    # Test database connection
    if php artisan tinker --execute="DB::connection()->getPdo(); echo 'DB: OK';" > /dev/null 2>&1; then
        log_success "Database connection: OK"
    else
        log_error "Database connection: FAILED"
    fi
    
    # Test queue workers
    if supervisorctl status gpr-worker:* | grep -q RUNNING; then
        log_success "Queue workers: RUNNING"
    else
        log_error "Queue workers: NOT RUNNING"
    fi
    
    # Test API endpoints
    if curl -s -o /dev/null -w "%{http_code}" "http://localhost/api/categories" | grep -q "200"; then
        log_success "API endpoints: OK"
    else
        log_warning "API endpoints: May need web server restart"
    fi
    
    # Check disk space
    DISK_USAGE=$(df / | awk 'NR==2 {print $5}' | sed 's/%//')
    if [ "$DISK_USAGE" -gt 90 ]; then
        log_warning "Disk usage is ${DISK_USAGE}% - consider cleanup"
    else
        log_success "Disk usage: ${DISK_USAGE}%"
    fi
    
    # Check memory usage
    MEM_USAGE=$(free | awk 'NR==2{printf "%.0f", $3*100/$2}')
    if [ "$MEM_USAGE" -gt 90 ]; then
        log_warning "Memory usage is ${MEM_USAGE}% - consider adding more RAM"
    else
        log_success "Memory usage: ${MEM_USAGE}%"
    fi
}

show_summary() {
    echo ""
    log_success "🎉 DEPLOYMENT COMPLETED SUCCESSFULLY!"
    echo ""
    echo "📋 SUMMARY:"
    echo "  - Backups created in: $BACKUP_DIR"
    echo "  - MySQL configuration optimized"
    echo "  - PHP configuration optimized"
    echo "  - Supervisor queue workers configured"
    echo "  - Cron jobs scheduled"
    echo "  - Laravel application cached and optimized"
    echo ""
    echo "🔧 NEXT STEPS:"
    echo "  1. Monitor application for 24 hours"
    echo "  2. Check queue workers: sudo supervisorctl status"
    echo "  3. Monitor logs: tail -f $APP_PATH/storage/logs/laravel.log"
    echo "  4. Test bulk operations performance"
    echo ""
    echo "📞 SUPPORT:"
    echo "  - Check logs in $APP_PATH/storage/logs/"
    echo "  - Rollback: Use backups in $BACKUP_DIR"
    echo "  - API Keys: php artisan api:key list"
    echo ""
}

# Main execution
main() {
    echo "🚀 Global Photo Rental - Production Deployment"
    echo "=============================================="
    echo "Started at: $(date)"
    echo ""
    
    if [ "$DRY_RUN" = true ]; then
        log_warning "DRY RUN MODE - No changes will be made"
        echo ""
    fi
    
    # Pre-flight checks
    check_root
    check_dependencies
    
    # Deployment steps
    create_backup
    optimize_mysql
    optimize_php
    setup_supervisor
    setup_cron_jobs
    setup_application
    
    # Post-deployment verification
    run_tests
    
    # Summary
    show_summary
    
    echo "Completed at: $(date)"
}

# Trap errors and provide cleanup
trap 'log_error "Deployment failed at line $LINENO. Check the logs and backups."' ERR

# Run main function
main "$@"
