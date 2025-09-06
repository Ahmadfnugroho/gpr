<?php

namespace App\Console\Commands;

use App\Models\ApiKey;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ManageApiKeys extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'api:key 
                            {action : Action to perform (create, list, delete, activate, deactivate)}
                            {--name= : Name for the API key}
                            {--key= : Specific API key to manage}
                            {--expires= : Expiration date (YYYY-MM-DD)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage API keys for the application';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');

        match ($action) {
            'create' => $this->createApiKey(),
            'list' => $this->listApiKeys(),
            'delete' => $this->deleteApiKey(),
            'activate' => $this->activateApiKey(),
            'deactivate' => $this->deactivateApiKey(),
            default => $this->error("Invalid action. Use: create, list, delete, activate, deactivate")
        };
    }

    private function createApiKey()
    {
        $name = $this->option('name') ?: $this->ask('Enter a name for the API key');
        $expires = $this->option('expires') ?: $this->ask('Enter expiration date (YYYY-MM-DD)', now()->addYear()->format('Y-m-d'));

        $apiKey = ApiKey::create([
            'name' => $name,
            'key' => 'gpr_' . Str::random(40),
            'active' => true,
            'expires_at' => $expires,
            'description' => 'Created via artisan command'
        ]);

        $this->info('API Key created successfully!');
        $this->table(
            ['ID', 'Name', 'Key', 'Active', 'Expires'],
            [[$apiKey->id, $apiKey->name, $apiKey->key, $apiKey->active ? 'Yes' : 'No', $apiKey->expires_at]]
        );

        return 0;
    }

    private function listApiKeys()
    {
        $apiKeys = ApiKey::all();

        if ($apiKeys->isEmpty()) {
            $this->warn('No API keys found.');
            return 0;
        }

        $this->table(
            ['ID', 'Name', 'Key', 'Active', 'Expires', 'Created'],
            $apiKeys->map(function ($key) {
                return [
                    $key->id,
                    $key->name,
                    Str::limit($key->key, 20) . '...',
                    $key->active ? 'Yes' : 'No',
                    $key->expires_at ? $key->expires_at->format('Y-m-d') : 'Never',
                    $key->created_at->format('Y-m-d H:i')
                ];
            })->toArray()
        );

        return 0;
    }

    private function deleteApiKey()
    {
        $key = $this->option('key') ?: $this->ask('Enter the API key to delete');

        $apiKey = ApiKey::where('key', $key)->first();

        if (!$apiKey) {
            $this->error('API key not found.');
            return 1;
        }

        if ($this->confirm("Are you sure you want to delete the API key '{$apiKey->name}'?")) {
            $apiKey->delete();
            $this->info('API key deleted successfully.');
        } else {
            $this->info('Operation cancelled.');
        }

        return 0;
    }

    private function activateApiKey()
    {
        $key = $this->option('key') ?: $this->ask('Enter the API key to activate');

        $apiKey = ApiKey::where('key', $key)->first();

        if (!$apiKey) {
            $this->error('API key not found.');
            return 1;
        }

        $apiKey->update(['active' => true]);
        $this->info("API key '{$apiKey->name}' activated successfully.");

        return 0;
    }

    private function deactivateApiKey()
    {
        $key = $this->option('key') ?: $this->ask('Enter the API key to deactivate');

        $apiKey = ApiKey::where('key', $key)->first();

        if (!$apiKey) {
            $this->error('API key not found.');
            return 1;
        }

        $apiKey->update(['active' => false]);
        $this->info("API key '{$apiKey->name}' deactivated successfully.");

        return 0;
    }
}
