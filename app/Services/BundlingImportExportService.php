<?php

namespace App\Services;

use App\Models\Bundling;
use App\Imports\BundlingImporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Exception;

class BundlingImportExportService
{
    /**
     * Export bundlings to Excel
     */
    public function exportBundlings(Request $request = null)
    {
        try {
            $query = Bundling::query();

            // Apply filters if provided
            if ($request) {
                if ($request->filled('search')) {
                    $search = $request->get('search');
                    $query->where(function($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                          ->orWhere('description', 'like', "%{$search}%");
                    });
                }

                if ($request->filled('status')) {
                    $query->where('status', $request->get('status'));
                }

                // Specific IDs for bulk export
                if ($request->filled('ids')) {
                    $ids = is_array($request->get('ids')) 
                        ? $request->get('ids') 
                        : explode(',', $request->get('ids'));
                    $query->whereIn('id', $ids);
                }
            }

            $bundlings = $query->orderBy('name')->get();

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Bundlings');

            // Set headers
            $headers = ['ID', 'Name', 'Description', 'Price', 'Status', 'Created At', 'Updated At'];
            $sheet->fromArray($headers, null, 'A1');

            // Style headers
            $headerStyle = [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
            ];
            $sheet->getStyle('A1:G1')->applyFromArray($headerStyle);

            // Auto-size columns
            foreach (range('A', 'G') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            // Add data
            $row = 2;
            foreach ($bundlings as $bundling) {
                $sheet->setCellValue('A' . $row, $bundling->id);
                $sheet->setCellValue('B' . $row, $bundling->name);
                $sheet->setCellValue('C' . $row, $bundling->description ?? '');
                $sheet->setCellValue('D' . $row, $bundling->price ?? 0);
                $sheet->setCellValue('E' . $row, $bundling->status ?? 'active');
                $sheet->setCellValue('F' . $row, $bundling->created_at ? $bundling->created_at->format('Y-m-d H:i:s') : '');
                $sheet->setCellValue('G' . $row, $bundling->updated_at ? $bundling->updated_at->format('Y-m-d H:i:s') : '');

                // Add borders to data rows
                $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
                ]);

                $row++;
            }

            // Generate filename
            $filename = 'bundlings_export_' . date('Y_m_d_H_i_s') . '.xlsx';
            $filePath = 'exports/' . $filename;

            // Save to storage
            $writer = new Xlsx($spreadsheet);
            $tempFile = tempnam(sys_get_temp_dir(), 'bundling_export');
            $writer->save($tempFile);

            Storage::put($filePath, file_get_contents($tempFile));
            unlink($tempFile);

            Log::info('Bundling export completed', [
                'total_records' => $bundlings->count(),
                'filename' => $filename,
                'filters' => $request ? $request->all() : []
            ]);

            return [
                'success' => true,
                'filename' => $filename,
                'filepath' => $filePath,
                'total_records' => $bundlings->count(),
                'url' => Storage::url($filePath)
            ];

        } catch (Exception $e) {
            Log::error('Bundling export failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw new Exception('Export failed: ' . $e->getMessage());
        }
    }

    /**
     * Import bundlings from Excel file
     */
    public function importBundlings(Request $request)
    {
        try {
            $file = $request->file('file');
            $updateExisting = $request->boolean('update_existing', false);

            if (!$file) {
                throw new ValidationException(validator([], []), [
                    'file' => ['File upload diperlukan']
                ]);
            }

            // Validate file type
            if (!in_array($file->getClientOriginalExtension(), ['xlsx', 'xls', 'csv'])) {
                throw new ValidationException(validator([], []), [
                    'file' => ['File harus berformat Excel (.xlsx, .xls) atau CSV (.csv)']
                ]);
            }

            // Create importer instance
            $importer = new BundlingImporter($updateExisting);

            // Import the file
            Excel::import($importer, $file);

            // Get import results
            $results = $importer->getImportResults();

            Log::info('Bundling import completed', [
                'filename' => $file->getClientOriginalName(),
                'results' => $results,
                'update_existing' => $updateExisting
            ]);

            return [
                'success' => true,
                'message' => 'Import berhasil diproses',
                'results' => $results
            ];

        } catch (ValidationException $e) {
            Log::warning('Bundling import validation failed', [
                'errors' => $e->errors()
            ]);
            throw $e;

        } catch (Exception $e) {
            Log::error('Bundling import failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw new Exception('Import failed: ' . $e->getMessage());
        }
    }

    /**
     * Download import template
     */
    public function downloadTemplate()
    {
        try {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Bundlings Template');

            // Main data headers (columns A-E)
            $headers = BundlingImporter::getExpectedHeaders();
            $sheet->fromArray($headers, null, 'A1');

            // Sample data in row 2
            $sampleData = [
                '', // id (kosong untuk create, isi untuk update)
                'Bundling Paket Hemat A',
                'Deskripsi bundling paket hemat untuk acara kecil',
                '500000',
                'active'
            ];
            $sheet->fromArray($sampleData, null, 'A2');

            // Style headers
            $headerStyle = [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
            ];
            $sheet->getStyle('A1:E1')->applyFromArray($headerStyle);

            // Style sample data
            $sheet->getStyle('A2:E2')->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2EFDA']],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
            ]);

            // Auto-size columns A-E
            foreach (range('A', 'E') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            // Add instructions in column H
            $instructions = [
                ['PANDUAN IMPORT BUNDLING'],
                [''],
                ['Kolom yang diperlukan:'],
                ['• id: Kosong untuk data baru, isi ID untuk update'],
                ['• name: Nama bundling (wajib)'],
                ['• description: Deskripsi bundling (opsional)'],
                ['• price: Harga bundling (angka, opsional)'],
                ['• status: active/inactive (default: active)'],
                [''],
                ['Tips:'],
                ['• Jangan ubah header kolom'],
                ['• Kosongkan kolom ID untuk data baru'],
                ['• Isi kolom ID dengan angka untuk update data'],
                ['• Status hanya boleh: active, inactive'],
                ['• Harga dalam angka tanpa titik/koma'],
                [''],
                ['Contoh status yang valid:'],
                ['• active'],
                ['• inactive'],
            ];

            $sheet->fromArray($instructions, null, 'H1');
            $sheet->getStyle('H1')->applyFromArray([
                'font' => ['bold' => true, 'size' => 12],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D9E2F3']]
            ]);

            // Auto-size column H
            $sheet->getColumnDimension('H')->setAutoSize(true);

            // Generate filename and save
            $filename = 'bundlings_import_template_' . date('Y_m_d_H_i_s') . '.xlsx';
            $filePath = 'templates/' . $filename;

            $writer = new Xlsx($spreadsheet);
            $tempFile = tempnam(sys_get_temp_dir(), 'bundling_template');
            $writer->save($tempFile);

            Storage::put($filePath, file_get_contents($tempFile));
            unlink($tempFile);

            Log::info('Bundling import template generated', ['filename' => $filename]);

            return [
                'success' => true,
                'filename' => $filename,
                'filepath' => $filePath,
                'url' => Storage::url($filePath)
            ];

        } catch (Exception $e) {
            Log::error('Bundling template generation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw new Exception('Template generation failed: ' . $e->getMessage());
        }
    }
}
