<?php

namespace App\Jobs;

use App\Imports\CustomerImporter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Exception;

class ImportCustomersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $filePath;
    protected $updateExisting;
    protected $userId;
    protected $importId;

    public $timeout = 300; // 5 minutes timeout
    public $tries = 3; // Try up to 3 times
    public $maxExceptions = 1;

    /**
     * Create a new job instance.
     */
    public function __construct(string $filePath, bool $updateExisting = false, ?int $userId = null, ?string $importId = null)
    {
        $this->filePath = $filePath;
        $this->updateExisting = $updateExisting;
        $this->userId = $userId;
        $this->importId = $importId ?? uniqid('import_');
        
        // Set queue priority
        $this->onQueue('imports');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info("Starting customer import job", [
                'import_id' => $this->importId,
                'file_path' => $this->filePath,
                'update_existing' => $this->updateExisting,
                'user_id' => $this->userId
            ]);

            // Check if file exists
            if (!Storage::exists($this->filePath)) {
                throw new Exception("Import file not found: {$this->filePath}");
            }

            // Create importer instance
            $importer = new CustomerImporter($this->updateExisting);

            // Set memory limit and execution time for large imports
            ini_set('memory_limit', '512M');
            set_time_limit(300);

            // Import the file
            Excel::import($importer, Storage::path($this->filePath));

            // Get results
            $results = $importer->getImportResults();

            // Log completion
            Log::info("Customer import job completed", [
                'import_id' => $this->importId,
                'results' => $results,
                'user_id' => $this->userId
            ]);

            // Store results for later retrieval (you can use cache, database, etc.)
            $this->storeImportResults($results);

            // Clean up uploaded file
            Storage::delete($this->filePath);

        } catch (Exception $e) {
            Log::error("Customer import job failed", [
                'import_id' => $this->importId,
                'error' => $e->getMessage(),
                'file_path' => $this->filePath,
                'user_id' => $this->userId
            ]);

            // Store error results
            $this->storeImportResults([
                'total' => 0,
                'success' => 0,
                'failed' => 0,
                'updated' => 0,
                'errors' => ["Import failed: " . $e->getMessage()]
            ]);

            throw $e;
        }
    }

    /**
     * Store import results for later retrieval
     */
    protected function storeImportResults(array $results): void
    {
        // Store results in cache with 1 hour expiration
        cache()->put("customer_import_results_{$this->importId}", [
            'results' => $results,
            'completed_at' => now(),
            'user_id' => $this->userId
        ], 3600);
    }

    /**
     * Handle a job failure.
     */
    public function failed(Exception $exception): void
    {
        Log::error("Customer import job permanently failed", [
            'import_id' => $this->importId,
            'error' => $exception->getMessage(),
            'file_path' => $this->filePath,
            'user_id' => $this->userId
        ]);

        // Store failure results
        $this->storeImportResults([
            'total' => 0,
            'success' => 0,
            'failed' => 0,
            'updated' => 0,
            'errors' => ["Import permanently failed: " . $exception->getMessage()]
        ]);

        // Clean up uploaded file
        if (Storage::exists($this->filePath)) {
            Storage::delete($this->filePath);
        }
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'customer-import',
            'import-' . $this->importId,
            'user-' . $this->userId
        ];
    }
}
