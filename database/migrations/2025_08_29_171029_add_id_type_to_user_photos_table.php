<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('user_photos', function (Blueprint $table) {
            $table->string('id_type')->nullable()->after('photo_type')->comment('Type of ID document (KK, SIM, NPWP, etc.)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_photos', function (Blueprint $table) {
            $table->dropColumn('id_type');
        });
    }
};
