<?php

require __DIR__ . '/vendor/autoload.php';

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Hash;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    // Create test user
    $testUser = User::create([
        'name' => 'Test User Email',
        'email' => 'test.registration@gmail.com',
        'password' => Hash::make('password123'),
        'status' => 'blacklist',
        'gender' => 'male',
        'address' => 'Test Address',
        'emergency_contact_name' => 'Emergency Contact',
        'emergency_contact_number' => '081234567890',
        'source_info' => 'Google',
    ]);
    
    echo "User created with ID: " . $testUser->id . "\n";
    
    // Trigger Registered event
    event(new Registered($testUser));
    
    echo "Registered event triggered for email verification\n";
    
    // Clean up test user
    $testUser->delete();
    echo "Test user deleted\n";
    
    echo "Email verification test completed successfully!\n";
    
} catch (Exception $e) {
    echo "Error during test: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
