<?php
/**
 * Script to clean and consolidate GPR migrations
 * This will create clean, consolidated migration files
 */

// Create directory for new clean migrations
$cleanMigrationsDir = 'database/migrations_clean';
if (!is_dir($cleanMigrationsDir)) {
    mkdir($cleanMigrationsDir, 0755, true);
}

echo "🧹 Cleaning and consolidating GPR migrations...\n";
echo "================================================\n\n";

// Define the clean migration structure
$consolidatedMigrations = [
    '2024_12_23_093000_create_base_tables.php' => [
        'description' => 'Create base application tables (users, categories, brands, etc.)',
        'tables' => ['users', 'categories', 'brands', 'sub_categories', 'api_keys', 'cache', 'jobs']
    ],
    '2024_12_23_093100_create_product_tables.php' => [
        'description' => 'Create product-related tables',
        'tables' => ['products', 'product_items', 'product_photos', 'product_specifications']
    ],
    '2024_12_23_093200_create_rental_transaction_tables.php' => [
        'description' => 'Create rental and transaction tables',
        'tables' => ['transactions', 'detail_transactions', 'rental_includes']
    ],
    '2024_12_23_093300_create_customer_tables.php' => [
        'description' => 'Create customer management tables',
        'tables' => ['customers', 'customer_photos', 'customer_phone_numbers']
    ],
    '2024_12_23_093400_create_bundling_promo_tables.php' => [
        'description' => 'Create bundling and promotion tables',
        'tables' => ['bundlings', 'bundling_products', 'bundling_photos', 'promos']
    ],
    '2024_12_23_093500_create_user_management_tables.php' => [
        'description' => 'Create user management and notification tables',
        'tables' => ['user_photos', 'user_phone_numbers', 'notifications']
    ],
    '2024_12_23_093600_create_system_tables.php' => [
        'description' => 'Create system and utility tables',
        'tables' => ['personal_access_tokens', 'imports', 'exports', 'failed_import_rows', 'export_settings', 'sync_logs', 'permissions', 'roles', 'activity_log']
    ],
    '2024_12_23_093700_create_pivot_tables.php' => [
        'description' => 'Create pivot and junction tables',
        'tables' => ['detail_transaction_product_item']
    ],
    '2024_12_23_093800_add_indexes_and_constraints.php' => [
        'description' => 'Add performance indexes and additional constraints',
        'tables' => []
    ]
];

// Function to create clean migration file
function createCleanMigration($filename, $data) {
    global $cleanMigrationsDir;
    
    $className = str_replace('.php', '', $filename);
    $className = implode('', array_map('ucfirst', explode('_', substr($className, 18)))); // Remove timestamp
    
    $content = "<?php\n\n";
    $content .= "use Illuminate\\Database\\Migrations\\Migration;\n";
    $content .= "use Illuminate\\Database\\Schema\\Blueprint;\n";
    $content .= "use Illuminate\\Support\\Facades\\Schema;\n";
    $content .= "use Illuminate\\Support\\Facades\\DB;\n\n";
    $content .= "return new class extends Migration\n{\n";
    $content .= "    /**\n     * Run the migrations.\n     */\n";
    $content .= "    public function up(): void\n    {\n";
    
    // Add the actual migration logic based on the filename
    switch ($filename) {
        case '2024_12_23_093000_create_base_tables.php':
            $content .= getBaseTablesSchema();
            break;
        case '2024_12_23_093100_create_product_tables.php':
            $content .= getProductTablesSchema();
            break;
        case '2024_12_23_093200_create_rental_transaction_tables.php':
            $content .= getRentalTransactionTablesSchema();
            break;
        case '2024_12_23_093300_create_customer_tables.php':
            $content .= getCustomerTablesSchema();
            break;
        case '2024_12_23_093400_create_bundling_promo_tables.php':
            $content .= getBundlingPromoTablesSchema();
            break;
        case '2024_12_23_093500_create_user_management_tables.php':
            $content .= getUserManagementTablesSchema();
            break;
        case '2024_12_23_093600_create_system_tables.php':
            $content .= getSystemTablesSchema();
            break;
        case '2024_12_23_093700_create_pivot_tables.php':
            $content .= getPivotTablesSchema();
            break;
        case '2024_12_23_093800_add_indexes_and_constraints.php':
            $content .= getIndexesAndConstraintsSchema();
            break;
    }
    
    $content .= "    }\n\n";
    $content .= "    /**\n     * Reverse the migrations.\n     */\n";
    $content .= "    public function down(): void\n    {\n";
    $content .= getDownMethod($data['tables']);
    $content .= "    }\n";
    $content .= "};\n";
    
    file_put_contents("$cleanMigrationsDir/$filename", $content);
    echo "✅ Created: $filename\n";
}

echo "Creating consolidated migration files...\n\n";

foreach ($consolidatedMigrations as $filename => $data) {
    createCleanMigration($filename, $data);
}

echo "\n🎉 Clean migrations created successfully!\n";
echo "📁 Location: $cleanMigrationsDir/\n";
echo "\n📋 Next steps:\n";
echo "1. Review the generated migration files\n";
echo "2. Backup your current database\n";
echo "3. Drop existing tables: php artisan migrate:reset\n";
echo "4. Replace old migrations with clean ones\n";
echo "5. Run new migrations: php artisan migrate\n";

function getBaseTablesSchema() {
    return '
        // Users table
        Schema::create(\'users\', function (Blueprint $table) {
            $table->id();
            $table->string(\'name\');
            $table->string(\'email\')->unique();
            $table->timestamp(\'email_verified_at\')->nullable();
            $table->string(\'password\');
            $table->rememberToken();
            $table->timestamps();
            
            $table->index(\'email\');
        });

        // Categories table
        Schema::create(\'categories\', function (Blueprint $table) {
            $table->id();
            $table->string(\'name\');
            $table->string(\'slug\')->unique();
            $table->timestamps();
            
            $table->index(\'slug\');
        });

        // Brands table
        Schema::create(\'brands\', function (Blueprint $table) {
            $table->id();
            $table->string(\'name\');
            $table->string(\'slug\')->unique();
            $table->string(\'logo\')->nullable();
            $table->boolean(\'premiere\')->default(false);
            $table->timestamps();
            
            $table->index(\'slug\');
            $table->index(\'premiere\');
        });

        // Sub categories table
        Schema::create(\'sub_categories\', function (Blueprint $table) {
            $table->id();
            $table->string(\'name\');
            $table->string(\'slug\')->unique();
            $table->foreignId(\'category_id\')->constrained()->cascadeOnDelete();
            $table->timestamps();
            
            $table->index(\'slug\');
            $table->index(\'category_id\');
        });

        // API Keys table
        Schema::create(\'api_keys\', function (Blueprint $table) {
            $table->id();
            $table->string(\'name\');
            $table->string(\'key\')->unique();
            $table->boolean(\'active\')->default(true);
            $table->timestamp(\'expires_at\')->nullable();
            $table->timestamps();
            
            $table->index([\'key\', \'active\']);
        });

        // Cache table
        Schema::create(\'cache\', function (Blueprint $table) {
            $table->string(\'key\')->primary();
            $table->mediumText(\'value\');
            $table->integer(\'expiration\');
        });

        Schema::create(\'cache_locks\', function (Blueprint $table) {
            $table->string(\'key\')->primary();
            $table->string(\'owner\');
            $table->integer(\'expiration\');
        });

        // Jobs table
        Schema::create(\'jobs\', function (Blueprint $table) {
            $table->id();
            $table->string(\'queue\')->index();
            $table->longText(\'payload\');
            $table->unsignedTinyInteger(\'attempts\');
            $table->unsignedInteger(\'reserved_at\')->nullable();
            $table->unsignedInteger(\'available_at\');
            $table->unsignedInteger(\'created_at\');
        });

        Schema::create(\'job_batches\', function (Blueprint $table) {
            $table->string(\'id\')->primary();
            $table->string(\'name\');
            $table->integer(\'total_jobs\');
            $table->integer(\'pending_jobs\');
            $table->integer(\'failed_jobs\');
            $table->longText(\'failed_job_ids\');
            $table->mediumText(\'options\')->nullable();
            $table->integer(\'cancelled_at\')->nullable();
            $table->integer(\'created_at\');
            $table->integer(\'finished_at\')->nullable();
        });

        Schema::create(\'failed_jobs\', function (Blueprint $table) {
            $table->id();
            $table->string(\'uuid\')->unique();
            $table->text(\'connection\');
            $table->text(\'queue\');
            $table->longText(\'payload\');
            $table->longText(\'exception\');
            $table->timestamp(\'failed_at\')->useCurrent();
        });
';
}

function getProductTablesSchema() {
    return '
        // Products table (consolidated with all fields)
        Schema::create(\'products\', function (Blueprint $table) {
            $table->id();
            $table->string(\'name\');
            $table->string(\'custom_id\')->unique();
            $table->string(\'slug\')->unique();
            $table->unsignedInteger(\'price\');
            $table->string(\'thumbnail\')->nullable();
            $table->enum(\'status\', [\'available\', \'unavailable\']);
            $table->boolean(\'premiere\')->default(false);
            $table->foreignId(\'category_id\')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId(\'brand_id\')->nullable()->constrained(\'brands\')->nullOnDelete();
            $table->foreignId(\'sub_category_id\')->nullable()->constrained(\'sub_categories\')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
            
            // Indexes for performance
            $table->index(\'name\');
            $table->index(\'slug\');
            $table->index(\'status\');
            $table->index(\'premiere\');
            $table->index([\'category_id\', \'brand_id\']);
        });

        // Product items (serial numbers)
        Schema::create(\'product_items\', function (Blueprint $table) {
            $table->id();
            $table->foreignId(\'product_id\')->constrained()->cascadeOnDelete();
            $table->string(\'serial_number\')->unique();
            $table->boolean(\'is_available\')->default(true);
            $table->timestamps();
            
            $table->index([\'product_id\', \'is_available\']);
            $table->index(\'serial_number\');
        });

        // Product photos
        Schema::create(\'product_photos\', function (Blueprint $table) {
            $table->id();
            $table->foreignId(\'product_id\')->constrained()->cascadeOnDelete();
            $table->string(\'photo\');
            $table->timestamps();
            
            $table->index(\'product_id\');
        });

        // Product specifications
        Schema::create(\'product_specifications\', function (Blueprint $table) {
            $table->id();
            $table->foreignId(\'product_id\')->constrained()->cascadeOnDelete();
            $table->string(\'name\');
            $table->text(\'value\')->nullable();
            $table->timestamps();
            
            $table->index(\'product_id\');
        });

        // Rental includes
        Schema::create(\'rental_includes\', function (Blueprint $table) {
            $table->id();
            $table->foreignId(\'product_id\')->constrained()->cascadeOnDelete();
            $table->foreignId(\'include_product_id\')->nullable()->constrained(\'products\')->cascadeOnDelete();
            $table->string(\'include_name\');
            $table->unsignedInteger(\'quantity\')->default(1);
            $table->timestamps();
            
            $table->index(\'product_id\');
            $table->index(\'include_product_id\');
        });
';
}

function getRentalTransactionTablesSchema() {
    return '
        // Transactions table (CONSOLIDATED - includes ALL fields from separate migrations)
        // Original: 2024_12_23_093214_create_transactions_table.php
        // Merged: 2025_09_01_210938_add_additional_services_to_transactions_table.php
        // Merged: 2025_09_01_213414_add_customer_id_to_transactions_table.php
        Schema::create(\'transactions\', function (Blueprint $table) {
            $table->id();
            $table->foreignId(\'user_id\')->constrained(\'users\')->cascadeOnDelete();
            $table->foreignId(\'customer_id\')->nullable()->constrained()->cascadeOnDelete(); // From add_customer_id migration
            $table->foreignId(\'promo_id\')->nullable()->constrained(\'promos\')->cascadeOnDelete()->nullable();
            $table->string(\'booking_transaction_id\')->unique();
            
            // Financial fields
            $table->unsignedInteger(\'grand_total\')->nullable();
            $table->unsignedInteger(\'down_payment\')->nullable();
            $table->unsignedInteger(\'remaining_payment\')->nullable();
            $table->unsignedInteger(\'cancellation_fee\')->nullable();
            
            // Booking details
            $table->enum(\'booking_status\', [\'booking\', \'paid\', \'on_rented\', \'done\', \'cancel\'])->default(\'booking\');
            $table->datetime(\'start_date\');
            $table->datetime(\'end_date\');
            $table->unsignedInteger(\'duration\');
            $table->text(\'note\')->nullable();
            
            // Additional services (From add_additional_services migration)
            $table->json(\'additional_services\')->nullable();
            
            // Additional fee fields (legacy structure)
            $table->string(\'additional_fee_1_name\')->nullable();
            $table->unsignedInteger(\'additional_fee_1_amount\')->nullable();
            $table->string(\'additional_fee_2_name\')->nullable();
            $table->unsignedInteger(\'additional_fee_2_amount\')->nullable();
            $table->string(\'additional_fee_3_name\')->nullable();
            $table->unsignedInteger(\'additional_fee_3_amount\')->nullable();
            
            $table->softDeletes();
            $table->timestamps();
            
            // Performance indexes
            $table->index(\'booking_transaction_id\');
            $table->index([\'booking_status\', \'start_date\', \'end_date\']);
            $table->index(\'user_id\');
            $table->index(\'customer_id\');
        });

        // Detail transactions
        Schema::create(\'detail_transactions\', function (Blueprint $table) {
            $table->id();
            $table->foreignId(\'transaction_id\')->constrained()->cascadeOnDelete();
            $table->foreignId(\'product_id\')->constrained()->cascadeOnDelete();
            $table->unsignedInteger(\'quantity\');
            $table->unsignedInteger(\'unit_price\');
            $table->unsignedInteger(\'total_price\');
            $table->text(\'note\')->nullable();
            $table->timestamps();
            
            $table->index([\'transaction_id\', \'product_id\']);
        });
';
}

function getCustomerTablesSchema() {
    return '
        // Customers table (consolidated)
        Schema::create(\'customers\', function (Blueprint $table) {
            $table->id();
            $table->string(\'name\');
            $table->string(\'email\')->unique();
            $table->string(\'google_id\')->nullable()->index();
            $table->timestamp(\'email_verified_at\')->nullable();
            $table->string(\'password\', 255)->default(\'123456789\');
            
            // Address and personal info
            $table->text(\'address\')->nullable();
            $table->string(\'job\')->nullable();
            $table->text(\'office_address\')->nullable();
            $table->enum(\'gender\', [\'male\', \'female\'])->nullable();
            
            // Social media and contacts
            $table->string(\'instagram_username\')->nullable();
            $table->string(\'facebook_username\')->nullable();
            $table->string(\'emergency_contact_name\')->nullable();
            $table->string(\'emergency_contact_number\')->nullable();
            
            // Status and source
            $table->string(\'source_info\')->nullable();
            $table->enum(\'status\', [\'active\', \'inactive\', \'blacklist\'])->default(\'blacklist\');
            
            $table->rememberToken();
            $table->timestamps();
            
            // Indexes
            $table->index(\'name\');
            $table->index(\'email\');
            $table->index(\'status\');
        });

        // Customer photos
        Schema::create(\'customer_photos\', function (Blueprint $table) {
            $table->id();
            $table->foreignId(\'customer_id\')->constrained()->cascadeOnDelete();
            $table->string(\'photo_type\'); // profile, id_card, etc.
            $table->string(\'photo\');
            $table->string(\'id_type\')->nullable(); // KTP, SIM, etc.
            $table->timestamps();
            
            $table->index([\'customer_id\', \'photo_type\']);
        });

        // Customer phone numbers
        Schema::create(\'customer_phone_numbers\', function (Blueprint $table) {
            $table->id();
            $table->foreignId(\'customer_id\')->constrained()->cascadeOnDelete();
            $table->string(\'phone_number\', 20);
            $table->boolean(\'is_primary\')->default(false);
            $table->timestamps();
            
            $table->index([\'customer_id\', \'phone_number\']);
        });
';
}

function getBundlingPromoTablesSchema() {
    return '
        // Promos table
        Schema::create(\'promos\', function (Blueprint $table) {
            $table->id();
            $table->string(\'name\');
            $table->string(\'code\')->unique();
            $table->text(\'description\')->nullable();
            $table->enum(\'type\', [\'percentage\', \'fixed\']);
            $table->unsignedInteger(\'value\');
            $table->unsignedInteger(\'min_transaction\')->nullable();
            $table->unsignedInteger(\'max_discount\')->nullable();
            $table->datetime(\'valid_from\');
            $table->datetime(\'valid_until\');
            $table->boolean(\'active\')->default(true);
            $table->timestamps();
            
            $table->index(\'code\');
            $table->index([\'active\', \'valid_from\', \'valid_until\']);
        });

        // Bundlings table
        Schema::create(\'bundlings\', function (Blueprint $table) {
            $table->id();
            $table->string(\'name\');
            $table->string(\'slug\')->unique();
            $table->text(\'description\')->nullable();
            $table->unsignedInteger(\'price\');
            $table->boolean(\'active\')->default(true);
            $table->timestamps();
            
            $table->index(\'slug\');
            $table->index(\'active\');
        });

        // Bundling products (pivot table)
        Schema::create(\'bundling_products\', function (Blueprint $table) {
            $table->id();
            $table->foreignId(\'bundling_id\')->constrained()->cascadeOnDelete();
            $table->foreignId(\'product_id\')->constrained()->cascadeOnDelete();
            $table->unsignedInteger(\'quantity\')->default(1);
            $table->timestamps();
            
            $table->index([\'bundling_id\', \'product_id\']);
        });

        // Bundling photos
        Schema::create(\'bundling_photos\', function (Blueprint $table) {
            $table->id();
            $table->foreignId(\'bundling_id\')->constrained(\'bundlings\')->cascadeOnDelete();
            $table->string(\'photo\');
            $table->softDeletes();
            $table->timestamps();
            
            $table->index(\'bundling_id\');
        });
';
}

function getUserManagementTablesSchema() {
    return '
        // User photos
        Schema::create(\'user_photos\', function (Blueprint $table) {
            $table->id();
            $table->foreignId(\'user_id\')->constrained()->cascadeOnDelete();
            $table->string(\'photo_type\'); // profile, avatar, etc.
            $table->string(\'photo\');
            $table->timestamps();
            
            $table->index([\'user_id\', \'photo_type\']);
        });

        // User phone numbers
        Schema::create(\'user_phone_numbers\', function (Blueprint $table) {
            $table->id();
            $table->foreignId(\'user_id\')->constrained()->cascadeOnDelete();
            $table->string(\'phone_number\', 20);
            $table->boolean(\'is_primary\')->default(false);
            $table->timestamps();
            
            $table->index([\'user_id\', \'phone_number\']);
        });

        // Notifications
        Schema::create(\'notifications\', function (Blueprint $table) {
            $table->id();
            $table->string(\'type\');
            $table->morphs(\'notifiable\');
            $table->text(\'data\');
            $table->timestamp(\'read_at\')->nullable();
            $table->timestamps();
            
            $table->index([\'notifiable_type\', \'notifiable_id\']);
        });
';
}

function getSystemTablesSchema() {
    return '
        // Sanctum personal access tokens
        Schema::create(\'personal_access_tokens\', function (Blueprint $table) {
            $table->id();
            $table->morphs(\'tokenable\');
            $table->string(\'name\');
            $table->string(\'token\', 64)->unique();
            $table->text(\'abilities\')->nullable();
            $table->timestamp(\'last_used_at\')->nullable();
            $table->timestamp(\'expires_at\')->nullable();
            $table->timestamps();
            
            $table->index([\'tokenable_type\', \'tokenable_id\']);
        });

        // Filament imports/exports
        Schema::create(\'imports\', function (Blueprint $table) {
            $table->id();
            $table->string(\'completed_at\')->nullable();
            $table->string(\'file_name\');
            $table->string(\'file_path\');
            $table->string(\'importer\');
            $table->json(\'processed_rows\')->default(0);
            $table->json(\'total_rows\');
            $table->json(\'successful_rows\')->default(0);
            $table->string(\'user_id\');
            $table->timestamps();
        });

        Schema::create(\'exports\', function (Blueprint $table) {
            $table->id();
            $table->string(\'completed_at\')->nullable();
            $table->string(\'file_disk\');
            $table->string(\'file_name\')->nullable();
            $table->string(\'exporter\');
            $table->json(\'processed_rows\')->default(0);
            $table->json(\'total_rows\');
            $table->json(\'successful_rows\')->default(0);
            $table->string(\'user_id\');
            $table->timestamps();
        });

        Schema::create(\'failed_import_rows\', function (Blueprint $table) {
            $table->id();
            $table->json(\'data\');
            $table->foreignId(\'import_id\')->constrained()->cascadeOnDelete();
            $table->text(\'validation_error\')->nullable();
            $table->timestamps();
        });

        Schema::create(\'export_settings\', function (Blueprint $table) {
            $table->id();
            $table->string(\'key\')->unique();
            $table->json(\'value\');
            $table->timestamps();
        });

        // Sync logs
        Schema::create(\'sync_logs\', function (Blueprint $table) {
            $table->id();
            $table->string(\'type\'); // google_sheet, api, etc.
            $table->string(\'status\'); // success, failed, partial
            $table->json(\'data\')->nullable();
            $table->text(\'message\')->nullable();
            $table->timestamps();
            
            $table->index([\'type\', \'status\']);
        });

        // Spatie permissions
        Schema::create(\'permissions\', function (Blueprint $table) {
            $table->id();
            $table->string(\'name\');
            $table->string(\'guard_name\');
            $table->timestamps();
            
            $table->unique([\'name\', \'guard_name\']);
        });

        Schema::create(\'roles\', function (Blueprint $table) {
            $table->id();
            $table->string(\'name\');
            $table->string(\'guard_name\');
            $table->timestamps();
            
            $table->unique([\'name\', \'guard_name\']);
        });

        Schema::create(\'model_has_permissions\', function (Blueprint $table) {
            $table->unsignedBigInteger(\'permission_id\');
            $table->string(\'model_type\');
            $table->unsignedBigInteger(\'model_id\');
            
            $table->index([\'model_id\', \'model_type\']);
            $table->foreign(\'permission_id\')->references(\'id\')->on(\'permissions\')->cascadeOnDelete();
            $table->primary([\'permission_id\', \'model_id\', \'model_type\']);
        });

        Schema::create(\'model_has_roles\', function (Blueprint $table) {
            $table->unsignedBigInteger(\'role_id\');
            $table->string(\'model_type\');
            $table->unsignedBigInteger(\'model_id\');
            
            $table->index([\'model_id\', \'model_type\']);
            $table->foreign(\'role_id\')->references(\'id\')->on(\'roles\')->cascadeOnDelete();
            $table->primary([\'role_id\', \'model_id\', \'model_type\']);
        });

        Schema::create(\'role_has_permissions\', function (Blueprint $table) {
            $table->unsignedBigInteger(\'permission_id\');
            $table->unsignedBigInteger(\'role_id\');
            
            $table->foreign(\'permission_id\')->references(\'id\')->on(\'permissions\')->cascadeOnDelete();
            $table->foreign(\'role_id\')->references(\'id\')->on(\'roles\')->cascadeOnDelete();
            $table->primary([\'permission_id\', \'role_id\']);
        });

        // Activity log (CONSOLIDATED - includes ALL fields from separate migrations)
        // Original: 2025_01_28_122906_create_activity_log_table.php
        // Merged: 2025_01_28_122907_add_event_column_to_activity_log_table.php
        // Merged: 2025_01_28_122908_add_batch_uuid_column_to_activity_log_table.php
        Schema::create(\'activity_log\', function (Blueprint $table) {
            $table->id();
            $table->string(\'log_name\')->nullable();
            $table->text(\'description\');
            $table->nullableMorphs(\'subject\', \'subject\');
            $table->nullableMorphs(\'causer\', \'causer\');
            $table->json(\'properties\')->nullable();
            $table->string(\'event\')->nullable(); // From add_event_column migration
            $table->string(\'batch_uuid\')->nullable(); // From add_batch_uuid_column migration
            $table->timestamps();
            
            $table->index(\'log_name\');
            $table->index([\'subject_type\', \'subject_id\']);
            $table->index([\'causer_type\', \'causer_id\']);
        });
';
}

function getPivotTablesSchema() {
    return '
        // Detail transaction product items (many-to-many)
        Schema::create(\'detail_transaction_product_item\', function (Blueprint $table) {
            $table->id();
            $table->foreignId(\'detail_transaction_id\')->constrained()->cascadeOnDelete();
            $table->foreignId(\'product_item_id\')->constrained()->cascadeOnDelete();
            $table->timestamps();
            
            $table->index([\'detail_transaction_id\', \'product_item_id\'], \'idx_detail_product\');
            $table->unique([\'detail_transaction_id\', \'product_item_id\']);
        });
';
}

function getIndexesAndConstraintsSchema() {
    return '
        // Add fulltext search index
        DB::statement(\'ALTER TABLE products ADD FULLTEXT INDEX ft_products_name (name)\');
        
        // Add additional performance indexes
        Schema::table(\'products\', function (Blueprint $table) {
            $table->index(\'premiere\', \'idx_products_premiere\');
            $table->index(\'status\', \'idx_products_status\');
            $table->index([\'category_id\', \'brand_id\'], \'idx_products_category_brand\');
        });
        
        Schema::table(\'categories\', function (Blueprint $table) {
            $table->index(\'slug\', \'idx_categories_slug\');
        });
        
        Schema::table(\'brands\', function (Blueprint $table) {
            $table->index(\'slug\', \'idx_brands_slug\');
        });
        
        Schema::table(\'sub_categories\', function (Blueprint $table) {
            $table->index(\'slug\', \'idx_sub_categories_slug\');
        });
        
        Schema::table(\'transactions\', function (Blueprint $table) {
            $table->index([\'booking_status\', \'start_date\', \'end_date\'], \'idx_status_dates\');
        });
        
        Schema::table(\'product_items\', function (Blueprint $table) {
            $table->index([\'product_id\', \'is_available\'], \'idx_product_available\');
        });
';
}

function getDownMethod($tables) {
    $content = '';
    foreach (array_reverse($tables) as $table) {
        $content .= "        Schema::dropIfExists('$table');\n";
    }
    return $content;
}
