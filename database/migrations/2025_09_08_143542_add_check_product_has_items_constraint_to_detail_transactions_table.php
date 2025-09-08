<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE detail_transactions 
            ADD CONSTRAINT check_product_has_items 
            CHECK (
                (product_id IS NULL) OR 
                (bundling_id IS NOT NULL) OR 
                (EXISTS(
                    SELECT 1 
                    FROM detail_transaction_product_item 
                    WHERE detail_transaction_id = detail_transactions.id
                ))
            )
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE detail_transactions 
            DROP CONSTRAINT check_product_has_items
        ");
    }
};
