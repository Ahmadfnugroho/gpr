<?php

namespace Database\Seeders;

use App\Models\ApiKey;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ApiKeySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default API key for frontend/mobile app
        ApiKey::firstOrCreate(
            ['name' => 'Frontend Application'],
            [
                'key' => 'gpr_frontend_' . Str::random(32),
                'active' => true,
                'expires_at' => now()->addYears(10), // Long expiry for production
                'description' => 'Default API key for frontend application access'
            ]
        );

        // Create development API key
        ApiKey::firstOrCreate(
            ['name' => 'Development'],
            [
                'key' => 'gpr_dev_test_key_12345678901234567890',
                'active' => true,
                'expires_at' => now()->addYears(1),
                'description' => 'Development and testing API key'
            ]
        );

        // Create admin API key for administrative operations
        ApiKey::firstOrCreate(
            ['name' => 'Admin Operations'],
            [
                'key' => 'gpr_admin_' . Str::random(40),
                'active' => true,
                'expires_at' => now()->addYear(),
                'description' => 'API key for administrative operations'
            ]
        );
    }
}
