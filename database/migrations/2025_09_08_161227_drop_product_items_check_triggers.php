<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared("DROP TRIGGER IF EXISTS check_product_has_items_before_insert;");
        DB::unprepared("DROP TRIGGER IF EXISTS check_product_has_items_before_update;");
        DB::unprepared("DROP TRIGGER IF EXISTS check_product_has_items_after_insert;");
        DB::unprepared("DROP TRIGGER IF EXISTS check_product_has_items_after_update;");
    }

    public function down(): void
    {
        // Kosong, karena kita tidak ingin membuat ulang trigger
    }
};
