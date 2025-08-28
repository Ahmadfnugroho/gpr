<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class ResetAdminPassword extends Command
{
    protected $signature = 'admin:reset-password {email} {password}';
    protected $description = 'Reset password for admin user';

    public function handle()
    {
        $email = $this->argument('email');
        $password = $this->argument('password');
        
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            $this->error("User with email {$email} not found!");
            $this->info("Available users:");
            
            User::select('id', 'name', 'email', 'status')
                ->get()
                ->each(fn($u) => $this->line("- {$u->name} ({$u->email}) - Status: {$u->status}"));
            
            return 1;
        }
        
        $user->update([
            'password' => Hash::make($password),
            'email_verified_at' => now() // Pastikan email terverifikasi
        ]);
        
        $this->info("✅ Password successfully reset for: {$user->name}");
        $this->info("📧 Email: {$email}");
        $this->info("🔑 New Password: {$password}");
        $this->info("📊 Status: {$user->status}");
        
        return 0;
    }
}
