# WARP.md

This file provides guidance to WARP (warp.dev) when working with code in this repository.

## Application Overview

Global Photo Rental (GPR) is a Laravel application for managing photo equipment rentals. The system is built with:
- **Backend**: Laravel 11 with Filament 3 admin panel
- **Database**: MySQL with activity logging via Spatie
- **Frontend**: Tailwind CSS, Livewire 3, and Vite
- **Authentication**: Laravel Sanctum with role-based permissions
- **External Integrations**: Google Sheets sync, WhatsApp notifications
- **Deployment**: Supports both traditional servers and Vercel

## Common Development Commands

### Local Development Setup
```bash
# Install PHP dependencies
composer install

# Install Node.js dependencies
npm install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Run migrations and seed database
php artisan migrate --seed

# Start all development services (server, queue, logs, vite)
composer run dev
```

### Individual Service Commands
```bash
# Start Laravel development server
php artisan serve

# Run queue worker
php artisan queue:listen --tries=1

# Watch logs in real-time
php artisan pail --timeout=0

# Start Vite development server
npm run dev

# Build assets for production
npm run build
```

### Testing
```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Feature/TransactionResourceTest.php

# Run tests with coverage
php artisan test --coverage
```

### Code Quality and Maintenance
```bash
# Format code with Laravel Pint
./vendor/bin/pint

# Clear and rebuild caches
php artisan optimize:clear
php artisan optimize

# Filament commands
php artisan filament:upgrade
php artisan filament:assets
php artisan filament:cache-components

# Shield (permissions) commands
php artisan shield:generate --all
php artisan shield:install
```

## Architecture Overview

### Domain Models and Business Logic
The application revolves around equipment rental management with these core entities:

- **Products**: Equipment items with categories, brands, specifications, and photos
- **ProductItems**: Individual units with serial numbers for inventory tracking
- **Transactions**: Rental bookings with start/end dates and payment tracking
- **Users**: Customers with detailed profiles, contact info, and rental history
- **Bundlings**: Product packages offered together
- **Promos**: Discount rules (day-based, percentage, nominal)

### Key Business Rules
- **Availability System**: Products are tracked by individual serial numbers, preventing double-booking
- **Transaction States**: pending → paid → rented → finished/cancelled
- **Automatic Calculations**: End dates computed from start date + duration
- **Promo Logic**: Complex discount rules based on rental duration or specific days
- **Activity Logging**: All admin actions tracked with Spatie ActivityLog

### Filament Admin Panel Structure
- **Navigation Groups**: User, Product, Transaction, Activity Log
- **Resources**: CRUD interfaces for all major entities with import/export capabilities
- **Policies**: Role-based access control via FilamentShield
- **Custom Components**: Serial number management, availability checking
- **Bulk Operations**: Status changes, imports, activity tracking

### API Architecture
The API uses key-based authentication via `api_key` middleware and provides:
- RESTful endpoints for products, categories, brands, transactions
- Slug-based routing for SEO-friendly URLs
- Transaction checking and booking endpoints
- Google Sheets synchronization endpoints

### Key Services and Observers
- **SerialNumberValidator**: Validates product item availability
- **TransactionObserver**: Handles booking lifecycle and inventory updates
- **UserObserver**: Manages user data synchronization
- **GoogleSheetSync**: Bidirectional sync with external sheets
- **WhatsApp Integration**: Transaction notifications via Fonnte

### Frontend Integration
- **Livewire Components**: Real-time UI updates for transactions and inventory
- **Vite Build System**: Asset compilation with hot reloading
- **Tailwind CSS**: Utility-first styling with custom components
- **PDF Generation**: Invoice and rental agreement generation

### Deployment Architecture
- **Traditional Deployment**: Via `deploy.sh` script with Git pull and optimization
- **Vercel Deployment**: Serverless PHP runtime with custom routing
- **Database**: AWS RDS MySQL with connection pooling
- **File Storage**: Public disk for product photos and documents
- **Queue System**: Database-based queues for background processing

## Important Development Notes

### Serial Number Management
The application tracks individual equipment units by serial numbers. When working with availability:
- Always check `ProductItem` availability for specific date ranges
- Use `getAvailableQuantityForPeriod()` and `getAvailableSerialNumbersForPeriod()` methods
- Inventory updates are handled automatically through observers

### Transaction Workflow
Transactions follow a strict state machine. When modifying booking logic:
- Start/end dates are automatically calculated from duration
- Serial number assignment happens during transaction creation
- Status changes trigger inventory updates via observers
- Always validate date ranges against existing bookings

### Google Sheets Integration
The system maintains bidirectional sync with Google Sheets:
- OAuth2 authentication required for sheet access
- Sync operations are queued to prevent timeouts
- User data, products, and transactions can be imported/exported
- Handle authentication redirects in Filament UI properly

### Performance Considerations
- Eager loading is configured for transaction relationships
- Use specific line ranges when reading large files (5000 line chunks)
- Product availability queries are optimized with database indexes
- Activity logs can grow large - consider archiving strategies

### Testing Strategy
Focus testing on:
- Serial number availability logic (`tests/Feature/AvailabilityTest.php`)
- Filament form validation (`tests/Feature/FilamentSerialNumberFormValidationTest.php`)
- Transaction state management (`tests/Feature/TransactionResourceTest.php`)
- Google Sheets synchronization (`tests/Feature/GoogleSheetSyncTest.php`)
