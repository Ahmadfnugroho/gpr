<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ProductImportExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ProductImportController extends Controller
{
    protected $importService;

    public function __construct(ProductImportExportService $importService)
    {
        $this->importService = $importService;
    }

    /**
     * Show import form
     */
    public function index()
    {
        return view('admin.import.product.index');
    }

    /**
     * Download import template
     */
    public function downloadTemplate()
    {
        try {
            $filePath = $this->importService->generateTemplate();
            
            if (!file_exists($filePath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Template file could not be generated'
                ], 500);
            }

            return response()->download($filePath, 'template_import_produk.xlsx')->deleteFileAfterSend();

        } catch (\Exception $e) {
            Log::error('Template download error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengunduh template: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Preview import data before actual import
     */
    public function preview(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240' // 10MB max
        ], [
            'file.required' => 'File import wajib dipilih',
            'file.mimes' => 'File harus berformat Excel (.xlsx, .xls) atau CSV',
            'file.max' => 'Ukuran file maksimal 10MB'
        ]);

        try {
            $preview = $this->importService->previewImport($request->file('file'));
            
            return response()->json([
                'success' => true,
                'data' => $preview
            ]);

        } catch (\Exception $e) {
            Log::error('Import preview error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat preview: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate file structure
     */
    public function validate(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240'
        ], [
            'file.required' => 'File import wajib dipilih',
            'file.mimes' => 'File harus berformat Excel (.xlsx, .xls) atau CSV',
            'file.max' => 'Ukuran file maksimal 10MB'
        ]);

        try {
            $validation = $this->importService->validateFileStructure($request->file('file'));
            
            return response()->json([
                'success' => true,
                'validation' => $validation
            ]);

        } catch (\Exception $e) {
            Log::error('File validation error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal memvalidasi file: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Execute import
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
            'update_existing' => 'sometimes|boolean'
        ], [
            'file.required' => 'File import wajib dipilih',
            'file.mimes' => 'File harus berformat Excel (.xlsx, .xls) atau CSV',
            'file.max' => 'Ukuran file maksimal 10MB'
        ]);

        try {
            $updateExisting = $request->boolean('update_existing', false);
            $results = $this->importService->importProducts($request->file('file'), $updateExisting);
            
            // Store failed rows in session if any
            if (!empty($results['failed_rows'])) {
                session(['failed_import_rows' => $results['failed_rows']]);
            }

            return response()->json([
                'success' => true,
                'results' => $results,
                'has_failed_rows' => !empty($results['failed_rows'])
            ]);

        } catch (\Exception $e) {
            Log::error('Import execution error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal melakukan import: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download failed import rows
     */
    public function downloadFailedRows(Request $request)
    {
        try {
            // Get failed rows from session or request
            $failedRows = session('failed_import_rows');
            
            if (empty($failedRows)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada data gagal untuk diunduh'
                ], 400);
            }

            $filePath = $this->importService->exportFailedImport($failedRows, 'product');
            
            if (!file_exists($filePath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'File gagal import tidak dapat dibuat'
                ], 500);
            }

            // Clear failed rows from session after download
            session()->forget('failed_import_rows');

            return response()->download($filePath, 'data_gagal_import_produk_' . date('Y-m-d_H-i-s') . '.xlsx')
                ->deleteFileAfterSend();

        } catch (\Exception $e) {
            Log::error('Failed rows download error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengunduh data gagal: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show import results page
     */
    public function results(Request $request)
    {
        $results = $request->session()->get('import_results', null);
        
        if (!$results) {
            return redirect()->route('admin.import.product.index')
                ->with('warning', 'Tidak ada hasil import untuk ditampilkan');
        }

        return view('admin.import.product.results', compact('results'));
    }

    /**
     * Clear import session data
     */
    public function clearSession()
    {
        session()->forget(['import_results', 'failed_import_rows']);
        
        return response()->json([
            'success' => true,
            'message' => 'Session data cleared'
        ]);
    }
}
