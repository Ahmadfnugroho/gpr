<?php

/**
 * Script untuk menghapus migrasi yang sudah tidak diperlukan
 * karena sudah dikonsolidasikan ke migrasi utama
 */

$migrationsDir = __DIR__ . '/database/migrations';
$backupDir = __DIR__ . '/database/migrations_backup_removed';

// Daftar migrasi yang sudah dikonsolidasikan dan bisa dihapus
$obsoleteMigrations = [
    // Activity Log - sudah digabung ke create_system_tables.php
    '2025_01_28_122907_add_event_column_to_activity_log_table.php',
    '2025_01_28_122908_add_batch_uuid_column_to_activity_log_table.php',
    
    // Transactions - sudah digabung ke create_rental_transaction_tables.php
    '2025_09_01_210938_add_additional_services_to_transactions_table.php',
    '2025_09_01_213414_add_customer_id_to_transactions_table.php',
    
    // Performance indexes - sudah digabung ke add_indexes_and_constraints.php
    '2025_09_02_040149_add_performance_indexes_to_tables.php'
];

echo "🗑️  CLEANING UP OBSOLETE MIGRATIONS\n";
echo "====================================\n\n";

// Buat folder backup jika belum ada
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
    echo "📁 Created backup directory: {$backupDir}\n";
}

$removedCount = 0;
$notFoundCount = 0;

foreach ($obsoleteMigrations as $migration) {
    $sourceFile = $migrationsDir . '/' . $migration;
    $backupFile = $backupDir . '/' . $migration;
    
    if (file_exists($sourceFile)) {
        // Backup dulu sebelum hapus
        copy($sourceFile, $backupFile);
        
        // Hapus file asli
        unlink($sourceFile);
        
        echo "✅ Removed: {$migration}\n";
        echo "   └─ Backed up to: migrations_backup_removed/\n";
        $removedCount++;
    } else {
        echo "⚠️  Not found: {$migration}\n";
        $notFoundCount++;
    }
}

echo "\n📊 CLEANUP SUMMARY:\n";
echo "===================\n";
echo "✅ Removed: {$removedCount} obsolete migrations\n";
echo "⚠️  Not found: {$notFoundCount} migrations\n";
echo "💾 All removed files backed up to: migrations_backup_removed/\n";

echo "\n🎯 CONSOLIDATION STATUS:\n";
echo "========================\n";
echo "✅ activity_log fields: event + batch_uuid → CONSOLIDATED\n";
echo "✅ transactions fields: customer_id + additional_services → CONSOLIDATED\n";  
echo "✅ performance indexes → CONSOLIDATED\n";

echo "\n🚀 NEXT STEPS:\n";
echo "==============\n";
echo "1. Run 'php replace-migrations.php' to use clean migrations\n";
echo "2. Test with 'php artisan migrate:fresh' (in development only!)\n";
echo "3. Verify all functionality works with consolidated schema\n";

echo "\n✨ Your migrations are now CLEAN and CONSOLIDATED! ✨\n";

?>
