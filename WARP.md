# WARP.md

This file provides guidance to WARP (warp.dev) when working with code in this repository.

## Project Overview

Global Photo Rental (GPR) is a comprehensive Laravel-based photo equipment rental management platform with:
- **Backend**: Laravel 11.x with PHP 8.1+
- **Admin Panel**: Filament v3 (TALL Stack: Tailwind, Alpine, Livewire)
- **Frontend**: Vite + Tailwind CSS
- **Database**: MySQL with optimized schema for inventory management
- **API**: RESTful API with Sanctum authentication and custom API key middleware
- **Integrations**: WhatsApp (WAHA), Google Sheets, Email notifications

## Development Commands

### Environment Setup
```bash
# Install dependencies
composer install
npm install

# Environment setup
cp .env.example .env
php artisan key:generate

# Database setup
php artisan migrate --seed

# Build assets
npm run build          # Production build
npm run dev           # Development with hot reload
```

### Development Server
```bash
# Option 1: Single command with all services
composer dev          # Runs server, queue, and vite concurrently

# Option 2: Manual services
php artisan serve
php artisan queue:work
npm run dev
```

### Testing
```bash
php artisan test                    # Run all tests
php artisan test --filter=Feature  # Feature tests only
php artisan test --filter=Unit     # Unit tests only
./vendor/bin/phpunit tests/Feature/ExampleTest.php  # Single test file
```

### Code Quality
```bash
./vendor/bin/pint                   # Laravel Pint (PHP-CS-Fixer)
php artisan optimize                # Route/config caching for production
```

### API & Keys
```bash
php artisan api:key create --name="Development"     # Create API key
php artisan api:key list                           # List all keys
php artisan api:key activate --key="YOUR_KEY"      # Activate key
php artisan api:key deactivate --key="YOUR_KEY"    # Deactivate key
```

### Queue Management
```bash
php artisan queue:work              # Process jobs
php artisan queue:listen            # Listen for new jobs
php artisan queue:monitor          # Real-time monitoring
php artisan queue:failed           # View failed jobs
php artisan queue:retry all        # Retry all failed jobs
```

### Cache Management
```bash
php artisan cache:clear            # Clear application cache
php artisan config:clear          # Clear config cache
php artisan route:clear           # Clear route cache
php artisan view:clear            # Clear compiled views
```

### Database Operations
```bash
php artisan migrate:refresh --seed  # Fresh migration with seeders
php artisan db:seed                 # Run seeders only
php artisan migrate:status          # Check migration status
```

## Architecture Overview

### Core Domain Models
- **Product**: Equipment items with specifications, photos, serial numbers, and availability tracking
- **Bundling**: Product combinations (e.g., wedding photography kit)
- **Transaction**: Rental bookings with date ranges, status tracking, and payment management
- **Customer**: Client management with contact info, addresses, and rental history
- **Category/SubCategory/Brand**: Product organization with premiere brand flagging

### Key Business Logic Locations
- **Inventory Management**: `app/Models/Product.php`, `app/Models/ProductItem.php`
  - Real-time availability checking with caching
  - Serial number tracking and assignment
  - Date-range conflict detection
- **Rental System**: `app/Models/Transaction.php`, `app/Models/DetailTransaction.php`
  - Booking status workflow (booking → paid → on_rented → completed)
  - Automatic pricing calculation with promo codes
  - WhatsApp notifications via observers
- **API Layer**: `app/Http/Controllers/Api/` and `routes/api.php`
  - Public endpoints (browsing, search, availability)
  - Protected endpoints (CRUD operations, sync)
  - Rate limiting and API key authentication

### Filament Admin Structure
- **Resources**: `app/Filament/Resources/` - CRUD interfaces for all models
- **Custom Components**: 
  - `app/Filament/Resources/TransactionResource/FormSections/` - Complex transaction forms
  - `app/Filament/Resources/TransactionResource/TableActions/` - Bulk operations
- **Imports/Exports**: `app/Filament/Imports/`, `app/Filament/Exports/` - Excel handling with memory optimization

### Integration Services
- **WhatsApp**: `app/Http/Controllers/Admin/WhatsAppController.php`, `app/Channels/WhatsAppChannel.php`
- **Google Sheets**: `app/Http/Controllers/GoogleSheetSyncController.php` 
- **Email**: `app/Mail/` and `app/Notifications/` for transaction updates

### Background Processing
- **Jobs**: `app/Jobs/` - Import processing, sync operations, bulk updates
- **Observers**: `app/Observers/` - Auto-sync, notifications, cache invalidation
- **Console Commands**: `app/Console/Commands/` - Maintenance, optimization, monitoring

## Development Guidelines

### Database Considerations
- Product availability uses complex date-range queries with caching
- Serial number assignment happens during transaction creation
- Use `actuallyAvailableForPeriod()` scope for accurate availability checks

### Performance Optimizations
- Inventory queries are cached (5-minute TTL)
- Bulk operations use chunking and queue processing
- Memory limits configured for large Excel imports

### API Usage Patterns
- Public endpoints: No auth, rate-limited (120/min)
- Protected endpoints: API key required, stricter limits (60/min)
- Availability checking: Separate endpoints for single/multiple items

### WhatsApp Integration
- Session management via WAHA service
- Authentication separate from main admin panel (`/whatsapp/login`)
- Automatic notifications on transaction status changes

### File Structure Navigation
- Models: Standard Laravel structure with extensive relationships
- Controllers: Separated by purpose (Api/, Admin/, Image/)
- Resources: Filament resources with custom form sections and table actions
- Tests: Feature and Unit tests (currently minimal - needs expansion)

### Environment Requirements
- PHP 8.1+ (composer.json specifies ^8.4)
- MySQL 8.0+ for JSON columns and advanced indexing
- Redis recommended for queue processing
- Node.js for Vite asset compilation

### Deployment Notes
- GitHub Actions workflow for production branch
- Custom .htaccess with security headers and performance optimization
- Queue supervisor setup required for background jobs
- Asset optimization via Vite for production builds
