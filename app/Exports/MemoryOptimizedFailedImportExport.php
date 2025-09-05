<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Illuminate\Support\Collection;

class MemoryOptimizedFailedImportExport implements 
    FromCollection, 
    WithHeadings, 
    WithMapping, 
    WithStyles, 
    WithCustomStartCell, 
    ShouldAutoSize,
    WithEvents
{
    protected array $failedRows;
    protected array $expectedHeaders;
    protected int $totalFailed;
    protected int $chunkSize;

    public function __construct(array $failedRows, array $expectedHeaders, int $chunkSize = 1000)
    {
        $this->failedRows = $failedRows;
        $this->expectedHeaders = $expectedHeaders;
        $this->totalFailed = count($failedRows);
        $this->chunkSize = $chunkSize;
    }

    /**
     * Return collection in chunks to prevent memory issues
     */
    public function collection()
    {
        // Process in chunks to prevent memory exhaustion
        return collect($this->failedRows)->chunk($this->chunkSize)->flatten(1);
    }

    /**
     * Define headers for the export
     */
    public function headings(): array
    {
        $headers = [
            'Row Number',
            'Error Reason',
            'Suggested Fix',
            'Status'
        ];

        // Add original data headers
        foreach ($this->expectedHeaders as $header) {
            $headers[] = ucfirst(str_replace('_', ' ', $header));
        }

        return $headers;
    }

    /**
     * Map each row for export
     */
    public function map($failedRow): array
    {
        $mappedRow = [
            $failedRow['row_number'] ?? 'N/A',
            $failedRow['error_reason'] ?? 'Unknown error',
            $this->getSuggestedFix($failedRow['error_reason'] ?? ''),
            'FAILED'
        ];

        // Add original row data
        $rowData = $failedRow['row_data'] ?? [];
        foreach ($this->expectedHeaders as $header) {
            $mappedRow[] = $rowData[$header] ?? '';
        }

        return $mappedRow;
    }

    /**
     * Get suggested fix for error
     */
    private function getSuggestedFix(string $errorReason): string
    {
        if (stripos($errorReason, 'sudah ada') !== false) {
            return 'Enable "Update Existing" or remove duplicate from file';
        } elseif (stripos($errorReason, 'wajib diisi') !== false) {
            return 'Fill the required field with valid data';
        } elseif (stripos($errorReason, 'tidak ditemukan') !== false) {
            return 'Check referenced ID/name exists in database';
        } elseif (stripos($errorReason, 'format') !== false) {
            return 'Check data format (date, email, etc.)';
        } else {
            return 'Check data according to expected format';
        }
    }

    /**
     * Set starting cell
     */
    public function startCell(): string
    {
        return 'A1';
    }

    /**
     * Apply styles to the worksheet
     */
    public function styles(Worksheet $sheet)
    {
        $totalRows = $this->totalFailed + 1; // +1 for header
        
        return [
            // Header row styling
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size' => 12
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'DC2626'] // Red background
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER
                ]
            ],
            
            // All data styling
            "A1:Z{$totalRows}" => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'E5E7EB']
                    ]
                ]
            ]
        ];
    }

    /**
     * Register events for additional styling
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $totalRows = $this->totalFailed + 1;
                
                // Set row heights
                $sheet->getDefaultRowDimension()->setRowHeight(20);
                $sheet->getRowDimension('1')->setRowHeight(25);
                
                // Color coding for different columns
                // Error Reason column (B) - light red background
                $sheet->getStyle("B2:B{$totalRows}")->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'FEE2E2'] // Light red
                    ]
                ]);
                
                // Suggested Fix column (C) - light blue background
                $sheet->getStyle("C2:C{$totalRows}")->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'DBEAFE'] // Light blue
                    ]
                ]);
                
                // Status column (D) - light yellow background
                $sheet->getStyle("D2:D{$totalRows}")->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'FEF3C7'] // Light yellow
                    ],
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'B91C1C'] // Red text
                    ]
                ]);
                
                // Add summary at the bottom
                $summaryRow = $totalRows + 2;
                $sheet->setCellValue("A{$summaryRow}", 'SUMMARY');
                $sheet->setCellValue("A" . ($summaryRow + 1), "Total Failed Rows: {$this->totalFailed}");
                $sheet->setCellValue("A" . ($summaryRow + 2), "Export Date: " . now()->format('Y-m-d H:i:s'));
                $sheet->setCellValue("A" . ($summaryRow + 3), "Instructions: Fix the errors and re-import this file");
                
                // Style summary
                $sheet->getStyle("A{$summaryRow}:A" . ($summaryRow + 3))->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => '1F2937']
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F3F4F6']
                    ]
                ]);
                
                // Memory optimization - freeze panes to help with large datasets
                $sheet->freezePane('E2');
            }
        ];
    }
}
