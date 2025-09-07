<?php

namespace App\Services;

use App\Models\BundlingPhoto;
use App\Models\Bundling;
use App\\Filament\\Imports\\BundlingPhotoImporter;
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

class BundlingPhotoImportExportService
{
    /**
     * Export bundling photos to Excel
     */
    public function exportBundlingPhotos(Request $request = null)
    {
        try {
            $query = BundlingPhoto::with('bundling');

            // Apply filters if provided
            if ($request) {
                if ($request->filled('bundling_id')) {
                    $query->where('bundling_id', $request->get('bundling_id'));
                }

                if ($request->filled('search')) {
                    $search = $request->get('search');
                    $query->whereHas('bundling', function($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
                }

                // Specific IDs for bulk export
                if ($request->filled('ids')) {
                    $ids = is_array($request->get('ids')) 
                        ? $request->get('ids') 
                        : explode(',', $request->get('ids'));
                    $query->whereIn('id', $ids);
                }
            }

            $bundlingPhotos = $query->orderBy('bundling_id')->get();

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Bundling Photos');

            // Set headers for main data (columns A-E)
            $headers = ['ID', 'Bundling ID', 'Bundling Name', 'Photo', 'Created At'];
            $sheet->fromArray($headers, null, 'A1');

            // Style headers
            $headerStyle = [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
            ];
            $sheet->getStyle('A1:E1')->applyFromArray($headerStyle);

            // Auto-size columns
            foreach (range('A', 'E') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            // Add data
            $row = 2;
            foreach ($bundlingPhotos as $bundlingPhoto) {
                $sheet->setCellValue('A' . $row, $bundlingPhoto->id);
                $sheet->setCellValue('B' . $row, $bundlingPhoto->bundling_id);
                $sheet->setCellValue('C' . $row, $bundlingPhoto->bundling->name ?? '');
                $sheet->setCellValue('D' . $row, $bundlingPhoto->photo ?? '');
                $sheet->setCellValue('E' . $row, $bundlingPhoto->created_at ? $bundlingPhoto->created_at->format('Y-m-d H:i:s') : '');

                // Add borders to data rows
                $sheet->getStyle('A' . $row . ':E' . $row)->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
                ]);

                $row++;
            }

            // Add reference data for bundlings in columns H-J
            $this->addBundlingReferenceData($sheet);

            // Generate filename
            $filename = 'bundling_photos_export_' . date('Y_m_d_H_i_s') . '.xlsx';
            $filePath = 'exports/' . $filename;

            // Save to storage
            $writer = new Xlsx($spreadsheet);
            $tempFile = tempnam(sys_get_temp_dir(), 'bundling_photo_export');
            $writer->save($tempFile);

            Storage::put($filePath, file_get_contents($tempFile));
            unlink($tempFile);

            Log::info('Bundling photo export completed', [
                'total_records' => $bundlingPhotos->count(),
                'filename' => $filename,
                'filters' => $request ? $request->all() : []
            ]);

            return [
                'success' => true,
                'filename' => $filename,
                'filepath' => $filePath,
                'total_records' => $bundlingPhotos->count(),
                'url' => Storage::url($filePath)
            ];

        } catch (Exception $e) {
            Log::error('Bundling photo export failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw new Exception('Export failed: ' . $e->getMessage());
        }
    }

    /**
     * Import bundling photos from Excel file
     */
    public function importBundlingPhotos(Request $request)
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
            $importer = new BundlingPhotoImporter($updateExisting);

            // Import the file
            Excel::import($importer, $file);

            // Get import results
            $results = $importer->getImportResults();

            Log::info('Bundling photo import completed', [
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
            Log::warning('Bundling photo import validation failed', [
                'errors' => $e->errors()
            ]);
            throw $e;

        } catch (Exception $e) {
            Log::error('Bundling photo import failed', [
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
            $sheet->setTitle('Bundling Photos Template');

            // Main data headers (columns A-B)
            $headers = BundlingPhotoImporter::getExpectedHeaders();
            $sheet->fromArray($headers, null, 'A1');

            // Sample data in row 2
            $sampleData = [
                '1', // bundling_id
                'bundling_photo_1.jpg', // photo
            ];
            $sheet->fromArray($sampleData, null, 'A2');

            // Style headers
            $headerStyle = [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
            ];
            $sheet->getStyle('A1:B1')->applyFromArray($headerStyle);

            // Style sample data
            $sheet->getStyle('A2:B2')->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2EFDA']],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
            ]);

            // Auto-size columns A-B
            foreach (range('A', 'B') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            // Add bundling reference data
            $this->addBundlingReferenceData($sheet);

            // Add instructions in column E
            $instructions = [
                ['PANDUAN IMPORT BUNDLING PHOTO'],
                [''],
                ['Kolom yang diperlukan:'],
                ['• bundling_id: ID bundling (wajib, lihat referensi)'],
                ['• photo: Nama file photo (wajib)'],
                [''],
                ['Tips:'],
                ['• Jangan ubah header kolom'],
                ['• Pastikan bundling_id ada di referensi sebelah kanan'],
                ['• Photo bisa berupa nama file atau URL'],
                ['• Gunakan ekstensi file yang sesuai (.jpg, .png, dll)'],
                [''],
                ['Format photo yang didukung:'],
                ['• Nama file: product_photo_1.jpg'],
                ['• URL: https://example.com/image.jpg'],
                ['• Path relatif: images/bundling/photo.png'],
            ];

            $sheet->fromArray($instructions, null, 'E1');
            $sheet->getStyle('E1')->applyFromArray([
                'font' => ['bold' => true, 'size' => 12],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D9E2F3']]
            ]);

            // Auto-size column E
            $sheet->getColumnDimension('E')->setAutoSize(true);

            // Generate filename and save
            $filename = 'bundling_photos_import_template_' . date('Y_m_d_H_i_s') . '.xlsx';
            $filePath = 'templates/' . $filename;

            $writer = new Xlsx($spreadsheet);
            $tempFile = tempnam(sys_get_temp_dir(), 'bundling_photo_template');
            $writer->save($tempFile);

            Storage::put($filePath, file_get_contents($tempFile));
            unlink($tempFile);

            Log::info('Bundling photo import template generated', ['filename' => $filename]);

            return [
                'success' => true,
                'filename' => $filename,
                'filepath' => $filePath,
                'url' => Storage::url($filePath)
            ];

        } catch (Exception $e) {
            Log::error('Bundling photo template generation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw new Exception('Template generation failed: ' . $e->getMessage());
        }
    }

    /**
     * Add bundling reference data to the sheet
     */
    private function addBundlingReferenceData($sheet)
    {
        // Add bundling reference headers in columns H-I
        $refHeaders = ['Bundling ID', 'Bundling Name'];
        $sheet->fromArray($refHeaders, null, 'H1');

        // Style reference headers
        $refHeaderStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FF6B35']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ];
        $sheet->getStyle('H1:I1')->applyFromArray($refHeaderStyle);

        // Get bundlings for reference
        $bundlings = Bundling::select('id', 'name')->orderBy('name')->get();
        $refRow = 2;
        
        foreach ($bundlings as $bundling) {
            $sheet->setCellValue('H' . $refRow, $bundling->id);
            $sheet->setCellValue('I' . $refRow, $bundling->name);
            
            // Add borders to reference data
            $sheet->getStyle('H' . $refRow . ':I' . $refRow)->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFF2CC']]
            ]);
            
            $refRow++;
        }

        // Auto-size reference columns
        foreach (range('H', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }
}
