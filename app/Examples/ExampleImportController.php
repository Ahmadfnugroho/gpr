<?php

namespace App\Http\Controllers;

use App\Imports\ProductImporter;
use App\Traits\EnhancedImportControllerTrait;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ExampleImportController extends Controller
{
    use EnhancedImportControllerTrait;

    /**
     * Process product import with enhanced error handling
     */
    public function importProducts(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240', // 10MB max
            'update_existing' => 'boolean'
        ]);

        $updateExisting = $request->boolean('update_existing', false);
        
        return $this->processEnhancedImport($request, ProductImporter::class, $updateExisting);
    }

    /**
     * Example of how the response will look
     */
    public function exampleResponse()
    {
        // This is what you'll get back from processEnhancedImport:
        return [
            'success' => true,
            'message' => 'Import completed successfully',
            'data' => [
                'total' => 597,
                'success' => 169,
                'updated' => 0,
                'failed' => 428,
                'errors' => [
                    'Baris 5: Produk \'Sony FE 28mm F2\' sudah ada',
                    'Baris 8: Produk \'Sony FE 28-70mm F3.5-6.6 OSS (Kit Lens)\' sudah ada',
                    // ... more errors
                ],
                'messages' => [
                    'Baris 3: Berhasil menambahkan produk Camera X',
                    // ... more success messages  
                ],
                'failed_rows' => [
                    [
                        'row_number' => 5,
                        'row_data' => [
                            'name' => 'Sony FE 28mm F2',
                            'price' => '5000000',
                            // ... other row data
                        ],
                        'error_reason' => 'Produk \'Sony FE 28mm F2\' sudah ada'
                    ],
                    // ... more failed rows
                ]
            ]
        ];
    }

    /**
     * Example of the enhanced notification that will be shown
     */
    public function exampleNotification()
    {
        /*
        The user will see a notification like this:

        Title: ⚠️ Import Selesai dengan Error
        
        Body: Total: 597, Berhasil: 169, Diupdate: 0, Gagal: 428

        Contoh Error:
        Baris 5: Produk 'Sony FE 28mm F2' sudah ada
        Baris 8: Produk 'Sony FE 28-70mm F3.5-6.6 OSS (Kit Lens)' sudah ada  
        Baris 22: Produk 'Sony FE 85mm F1.8' sudah ada
        ... dan 425 error lainnya

        Actions:
        [Download Failed Rows] [Lihat Semua Error]
        */
    }
}

/**
 * Alternative: Use in Filament Resource Action
 */
class FilamentResourceExample
{
    public function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('import')
                ->label('Import Products')
                ->form([
                    \Filament\Forms\Components\FileUpload::make('file')
                        ->label('Excel File')
                        ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                        ->required(),
                    \Filament\Forms\Components\Toggle::make('update_existing')
                        ->label('Update Existing Products')
                        ->default(false),
                ])
                ->action(function (array $data) {
                    // Use the enhanced import trait
                    $controller = new class extends Controller {
                        use EnhancedImportControllerTrait;
                    };

                    $request = request();
                    $request->files->set('file', $data['file']);
                    
                    $response = $controller->processEnhancedImport(
                        $request, 
                        ProductImporter::class, 
                        $data['update_existing']
                    );

                    if ($response->getData()->success) {
                        \Filament\Notifications\Notification::make()
                            ->title('Import started')
                            ->body('Import process has been initiated. You will receive detailed results shortly.')
                            ->success()
                            ->send();
                    } else {
                        \Filament\Notifications\Notification::make()
                            ->title('Import failed')
                            ->body($response->getData()->message)
                            ->danger()
                            ->send();
                    }
                })
        ];
    }
}

/**
 * How to integrate with existing import pages
 */
class ExistingImportPageExample
{
    public function mount(): void
    {
        // Check if there are recent failed imports to show
        $controller = new class extends Controller {
            use EnhancedImportControllerTrait;
        };
        
        $statistics = $controller->getImportStatistics();
        
        if ($statistics) {
            \Filament\Notifications\Notification::make()
                ->title('Previous Import Errors Available')
                ->body("You have {$statistics['total_failed']} failed rows from a previous import.")
                ->warning()
                ->persistent()
                ->actions([
                    \Filament\Notifications\Actions\Action::make('download_failed')
                        ->label('Download Failed Rows')
                        ->url(route('import.download-failed'))
                        ->openUrlInNewTab(),
                    \Filament\Notifications\Actions\Action::make('view_errors')
                        ->label('View All Errors')
                        ->url(route('import.view-errors'))
                        ->openUrlInNewTab(),
                ])
                ->send();
        }
    }
}

/**
 * Complete working example with all features
 */
class CompleteImportController extends Controller
{
    use EnhancedImportControllerTrait;

    /**
     * Show import form
     */
    public function showImportForm()
    {
        $statistics = $this->getImportStatistics();
        
        return view('import.form', [
            'has_previous_errors' => !is_null($statistics),
            'statistics' => $statistics
        ]);
    }

    /**
     * Process import with full error handling
     */
    public function processImport(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
            'importer_type' => 'required|in:products,customers,categories',
            'update_existing' => 'boolean'
        ]);

        $importerClass = $this->getImporterClass($request->importer_type);
        $updateExisting = $request->boolean('update_existing', false);

        return $this->processEnhancedImport($request, $importerClass, $updateExisting);
    }

    /**
     * Get importer class based on type
     */
    private function getImporterClass(string $type): string
    {
        return match($type) {
            'products' => \App\Imports\ProductImporter::class,
            'customers' => \App\Imports\CustomerImporter::class,
            'categories' => \App\Imports\CategoryImporter::class,
            default => throw new \InvalidArgumentException("Unknown importer type: {$type}")
        };
    }
}
