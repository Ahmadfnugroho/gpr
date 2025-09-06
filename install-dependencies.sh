#!/bin/bash

# =============================================================================
# 🔧 GLOBAL PHOTO RENTAL - PRE-DEPLOYMENT DEPENDENCY INSTALLER
# =============================================================================
# 
# This script installs all required dependencies for the main deployment script
#
# =============================================================================

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

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

check_root() {
    if [[ $EUID -ne 0 ]]; then
        log_error "This script must be run as root (use sudo)"
        exit 1
    fi
}

install_dependencies() {
    log_info "🔧 Installing required dependencies for Global Photo Rental deployment..."
    
    # Update package list
    log_info "Updating package list..."
    apt-get update -qq
    
    # Install supervisor (queue worker manager)
    log_info "Installing Supervisor..."
    if ! command -v supervisorctl &> /dev/null; then
        apt-get install -y supervisor
        systemctl enable supervisor
        systemctl start supervisor
        log_success "Supervisor installed and started"
    else
        log_success "Supervisor already installed"
    fi
    
    # Install curl if not present (for health checks)
    log_info "Installing curl..."
    if ! command -v curl &> /dev/null; then
        apt-get install -y curl
        log_success "Curl installed"
    else
        log_success "Curl already installed"
    fi
    
    # Install unzip (for composer)
    log_info "Installing unzip..."
    if ! command -v unzip &> /dev/null; then
        apt-get install -y unzip
        log_success "Unzip installed"
    else
        log_success "Unzip already installed"
    fi
    
    # Install git (for composer dependencies)
    log_info "Installing git..."
    if ! command -v git &> /dev/null; then
        apt-get install -y git
        log_success "Git installed"
    else
        log_success "Git already installed"
    fi
    
    # Check PHP installation
    log_info "Checking PHP installation..."
    if ! command -v php &> /dev/null; then
        log_error "PHP is not installed. Please install PHP first:"
        log_info "sudo apt-get install -y php php-cli php-fpm php-mysql php-xml php-zip php-curl php-mbstring php-json php-bcmath php-tokenizer"
        exit 1
    else
        PHP_VERSION=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
        log_success "PHP $PHP_VERSION is installed"
    fi
    
    # Check MySQL/MariaDB installation
    log_info "Checking MySQL/MariaDB installation..."
    if ! command -v mysql &> /dev/null && ! command -v mariadb &> /dev/null; then
        log_error "MySQL/MariaDB is not installed. Please install it first:"
        log_info "sudo apt-get install -y mysql-server"
        exit 1
    else
        log_success "MySQL/MariaDB is installed"
    fi
    
    # Check Composer installation
    log_info "Checking Composer installation..."
    if ! command -v composer &> /dev/null; then
        log_info "Installing Composer..."
        curl -sS https://getcomposer.org/installer | php
        mv composer.phar /usr/local/bin/composer
        chmod +x /usr/local/bin/composer
        log_success "Composer installed"
    else
        log_success "Composer already installed"
    fi
    
    # Check web server (nginx or apache2)
    log_info "Checking web server installation..."
    if ! command -v nginx &> /dev/null && ! command -v apache2 &> /dev/null; then
        log_warning "No web server (nginx or apache2) detected"
        log_info "Installing nginx..."
        apt-get install -y nginx
        systemctl enable nginx
        systemctl start nginx
        log_success "Nginx installed and started"
    else
        if command -v nginx &> /dev/null; then
            log_success "Nginx is installed"
        elif command -v apache2 &> /dev/null; then
            log_success "Apache2 is installed"
        fi
    fi
    
    # Install additional PHP modules that might be needed
    log_info "Installing additional PHP modules..."
    PHP_VERSION=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
    apt-get install -y \
        php$PHP_VERSION-mysql \
        php$PHP_VERSION-xml \
        php$PHP_VERSION-zip \
        php$PHP_VERSION-curl \
        php$PHP_VERSION-mbstring \
        php$PHP_VERSION-json \
        php$PHP_VERSION-bcmath \
        php$PHP_VERSION-tokenizer \
        php$PHP_VERSION-gd \
        php$PHP_VERSION-intl \
        php$PHP_VERSION-opcache \
        php$PHP_VERSION-readline || log_warning "Some PHP modules might already be installed"
    
    log_success "All PHP modules installed"
}

verify_installation() {
    log_info "🔍 Verifying installation..."
    
    local deps=("mysql" "php" "composer" "supervisor" "curl")
    local missing=()
    
    for dep in "${deps[@]}"; do
        if ! command -v "$dep" &> /dev/null; then
            missing+=("$dep")
        fi
    done
    
    if [ ${#missing[@]} -ne 0 ]; then
        log_error "Still missing dependencies: ${missing[*]}"
        exit 1
    fi
    
    log_success "All dependencies are now installed!"
}

show_next_steps() {
    echo ""
    log_success "🎉 DEPENDENCIES INSTALLATION COMPLETED!"
    echo ""
    echo "📋 INSTALLED:"
    echo "  ✅ Supervisor (queue worker manager)"
    echo "  ✅ Curl (for health checks)"
    echo "  ✅ Git (for dependencies)"
    echo "  ✅ Composer (PHP dependency manager)"
    echo "  ✅ Additional PHP modules"
    echo ""
    echo "🚀 NEXT STEP:"
    echo "  Run the main deployment script:"
    echo "  sudo ./deploy-production.sh --dry-run"
    echo ""
    echo "  Or run full deployment:"
    echo "  sudo ./deploy-production.sh"
    echo ""
}

main() {
    echo "🔧 Global Photo Rental - Pre-Deployment Setup"
    echo "============================================="
    echo "Started at: $(date)"
    echo ""
    
    check_root
    install_dependencies
    verify_installation
    show_next_steps
    
    echo "Completed at: $(date)"
}

# Run main function
main "$@"
