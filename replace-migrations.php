<?php
/**
 * Script to replace old migrations with clean consolidated ones
 * CAUTION: This will backup and replace existing migrations
 */

echo "🔄 GPR Migration Replacement Script\n";
echo "===================================\n\n";

// Step 1: Create backup of existing migrations
$backupDir = 'database/migrations_backup_' . date('Y_m_d_H_i_s');
$migrationsDir = 'database/migrations';
$cleanMigrationsDir = 'database/migrations_clean';

echo "📦 Step 1: Creating backup of existing migrations...\n";

if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
    echo "✅ Created backup directory: $backupDir\n";
}

// Copy all existing migrations to backup
$existingMigrations = glob("$migrationsDir/*.php");
foreach ($existingMigrations as $migration) {
    $filename = basename($migration);
    copy($migration, "$backupDir/$filename");
    echo "📄 Backed up: $filename\n";
}

echo "\n📊 Backup Summary:\n";
echo "- Backed up " . count($existingMigrations) . " migration files\n";
echo "- Backup location: $backupDir\n\n";

// Step 2: Remove old migrations (keep Laravel defaults)
echo "🗑️ Step 2: Removing old migrations (keeping Laravel defaults)...\n";

$keepFiles = [
    '0001_01_01_000000_create_users_table.php',
    '0001_01_01_000001_create_cache_table.php', 
    '0001_01_01_000002_create_jobs_table.php'
];

foreach ($existingMigrations as $migration) {
    $filename = basename($migration);
    
    // Keep Laravel default migrations, remove others
    if (!in_array($filename, $keepFiles)) {
        unlink($migration);
        echo "🗑️ Removed: $filename\n";
    } else {
        echo "✅ Kept: $filename\n";
    }
}

// Step 3: Copy clean migrations to migrations directory  
echo "\n📋 Step 3: Installing clean consolidated migrations...\n";

$cleanMigrations = glob("$cleanMigrationsDir/*.php");
foreach ($cleanMigrations as $cleanMigration) {
    $filename = basename($cleanMigration);
    
    // Skip base tables migration (already covered by Laravel defaults)
    if ($filename === '2024_12_23_093000_create_base_tables.php') {
        echo "⏭️ Skipped: $filename (using Laravel defaults)\n";
        continue;
    }
    
    copy($cleanMigration, "$migrationsDir/$filename");
    echo "✅ Installed: $filename\n";
}

// Step 4: Update Laravel default migrations to include our additional tables
echo "\n🔧 Step 4: Updating Laravel default migrations...\n";

// Read and update users table migration
$usersTablePath = "$migrationsDir/0001_01_01_000000_create_users_table.php";
$usersContent = file_get_contents($usersTablePath);

// Check if we need to add categories, brands, sub_categories, api_keys
if (strpos($usersContent, 'categories') === false) {
    // Add our base tables to the users migration
    $additionalTables = "
        // Categories table
        Schema::create('categories', function (Blueprint \$table) {
            \$table->id();
            \$table->string('name');
            \$table->string('slug')->unique();
            \$table->timestamps();
            
            \$table->index('slug');
        });

        // Brands table
        Schema::create('brands', function (Blueprint \$table) {
            \$table->id();
            \$table->string('name');
            \$table->string('slug')->unique();
            \$table->string('logo')->nullable();
            \$table->boolean('premiere')->default(false);
            \$table->timestamps();
            
            \$table->index('slug');
            \$table->index('premiere');
        });

        // Sub categories table
        Schema::create('sub_categories', function (Blueprint \$table) {
            \$table->id();
            \$table->string('name');
            \$table->string('slug')->unique();
            \$table->foreignId('category_id')->constrained()->cascadeOnDelete();
            \$table->timestamps();
            
            \$table->index('slug');
            \$table->index('category_id');
        });

        // API Keys table
        Schema::create('api_keys', function (Blueprint \$table) {
            \$table->id();
            \$table->string('name');
            \$table->string('key')->unique();
            \$table->boolean('active')->default(true);
            \$table->timestamp('expires_at')->nullable();
            \$table->timestamps();
            
            \$table->index(['key', 'active']);
        });";

    // Insert after the users table creation
    $usersContent = str_replace(
        '    }',
        "    }$additionalTables",
        $usersContent
    );
    
    // Update down method
    $downMethod = "
        Schema::dropIfExists('api_keys');
        Schema::dropIfExists('sub_categories'); 
        Schema::dropIfExists('brands');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('users');";
        
    $usersContent = preg_replace(
        '/Schema::dropIfExists\([\'"]users[\'"]\);/',
        $downMethod,
        $usersContent
    );
    
    file_put_contents($usersTablePath, $usersContent);
    echo "✅ Updated users table migration with base tables\n";
}

// Step 5: Generate summary
echo "\n📊 Migration Replacement Summary:\n";
echo "=================================\n";
echo "✅ Original migrations backed up to: $backupDir\n";
echo "✅ Clean consolidated migrations installed\n";
echo "✅ " . count(glob("$migrationsDir/*.php")) . " migration files now in database/migrations/\n\n";

echo "📋 Migration Structure:\n";
$finalMigrations = glob("$migrationsDir/*.php");
foreach ($finalMigrations as $migration) {
    echo "  📄 " . basename($migration) . "\n";
}

echo "\n🎯 Next Steps:\n";
echo "1. Review the new migration structure above\n";
echo "2. If satisfied, run: php artisan migrate:fresh\n";
echo "3. If you need to rollback: restore from $backupDir\n";
echo "4. Seed your database with test data if needed\n\n";

echo "⚠️  WARNING: Running 'php artisan migrate:fresh' will DROP ALL TABLES!\n";
echo "   Make sure you have a database backup before proceeding.\n\n";

echo "🎉 Migration replacement completed successfully!\n";
?>
