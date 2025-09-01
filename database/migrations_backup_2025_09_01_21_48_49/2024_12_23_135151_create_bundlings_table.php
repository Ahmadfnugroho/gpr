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
        Schema::create('bundlings', function (Blueprint $table) {
            $table->id();
            $table->string('custom_id')->unique();
            $table->string('slug')->unique();

            $table->string('name');
            $table->unsignedInteger('price');
            $table->boolean('premiere')->default(false);



            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bundlings');
    }
};
