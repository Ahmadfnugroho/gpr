<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Trigger sebelum INSERT
        DB::unprepared("
            CREATE TRIGGER check_product_has_items_before_insert
            BEFORE INSERT ON detail_transactions
            FOR EACH ROW
            BEGIN
                IF NEW.product_id IS NOT NULL AND NEW.bundling_id IS NULL THEN
                    IF NOT EXISTS (
                        SELECT 1
                        FROM detail_transaction_product_item
                        WHERE detail_transaction_id = NEW.id
                    ) THEN
                        SIGNAL SQLSTATE '45000'
                            SET MESSAGE_TEXT = 'A product transaction must have product items or a bundling.';
                    END IF;
                END IF;
            END
        ");

        // Trigger sebelum UPDATE
        DB::unprepared("
            CREATE TRIGGER check_product_has_items_before_update
            BEFORE UPDATE ON detail_transactions
            FOR EACH ROW
            BEGIN
                IF NEW.product_id IS NOT NULL AND NEW.bundling_id IS NULL THEN
                    IF NOT EXISTS (
                        SELECT 1
                        FROM detail_transaction_product_item
                        WHERE detail_transaction_id = NEW.id
                    ) THEN
                        SIGNAL SQLSTATE '45000'
                            SET MESSAGE_TEXT = 'A product transaction must have product items or a bundling.';
                    END IF;
                END IF;
            END
        ");
    }

    public function down(): void
    {
        DB::unprepared("DROP TRIGGER IF EXISTS check_product_has_items_before_insert");
        DB::unprepared("DROP TRIGGER IF EXISTS check_product_has_items_before_update");
    }
};
