<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Illuminate\Support\Collection;

class FailedImportExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths
{
    protected $failedRows;
    protected $importType;

    public function __construct(array $failedRows, string $importType = 'product')
    {
        $this->failedRows = $failedRows;
        $this->importType = $importType;
    }

    /**
     * Return a collection of the failed rows
     */
    public function collection()
    {
        return collect($this->failedRows)->map(function ($row) {
            return [
                $row['row_number'],
                $row['nama_produk'],
                $row['harga'],
                $row['thumbnail'],
                $row['status'],
                $row['kategori'],
                $row['brand'],
                $row['sub_kategori'],
                $row['premiere'],
                $row['serial_numbers'],
                $row['error_message'],
                $row['suggestions']
            ];
        });
    }

    /**
     * Return the headings for the export
     */
    public function headings(): array
    {
        return [
            'Baris',
            'Nama Produk',
            'Harga',
            'Thumbnail',
            'Status',
            'Kategori',
            'Brand',
            'Sub Kategori',
            'Premiere',
            'Serial Numbers',
            'Error Message',
            'Saran Perbaikan'
        ];
    }

    /**
     * Style the Excel sheet
     */
    public function styles(Worksheet $sheet)
    {
        // Header row styling
        $sheet->getStyle('A1:L1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF']
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'DC3545'] // Bootstrap danger color
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                'wrapText' => true
            ]
        ]);

        // Error message column styling
        $lastRow = count($this->failedRows) + 1;
        $sheet->getStyle("K2:K{$lastRow}")->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FFE6E6'] // Light red background
            ],
            'alignment' => [
                'wrapText' => true
            ]
        ]);

        // Suggestions column styling
        $sheet->getStyle("L2:L{$lastRow}")->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E6F3FF'] // Light blue background
            ],
            'alignment' => [
                'wrapText' => true
            ]
        ]);

        // Row number column styling
        $sheet->getStyle("A2:A{$lastRow}")->applyFromArray([
            'font' => [
                'bold' => true
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F8F9FA'] // Light gray background
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
            ]
        ]);

        // Add borders
        $sheet->getStyle("A1:L{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '000000']
                ]
            ]
        ]);

        // Set row height for header
        $sheet->getRowDimension(1)->setRowHeight(25);

        // Add instructions at the top
        $sheet->insertNewRowBefore(1, 3);
        
        $sheet->setCellValue('A1', 'LAPORAN DATA GAGAL IMPORT');
        $sheet->mergeCells('A1:L1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 16,
                'color' => ['rgb' => 'DC3545']
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
            ]
        ]);

        $sheet->setCellValue('A2', 'Instruksi: Perbaiki data sesuai dengan kolom "Saran Perbaikan", lalu import ulang data ini.');
        $sheet->mergeCells('A2:L2');
        $sheet->getStyle('A2')->applyFromArray([
            'font' => [
                'italic' => true,
                'color' => ['rgb' => '6C757D']
            ],
            'alignment' => [
                'wrapText' => true
            ]
        ]);

        $sheet->setCellValue('A3', '');

        return [];
    }

    /**
     * Set column widths
     */
    public function columnWidths(): array
    {
        return [
            'A' => 8,   // Baris
            'B' => 25,  // Nama Produk
            'C' => 12,  // Harga
            'D' => 15,  // Thumbnail
            'E' => 12,  // Status
            'F' => 15,  // Kategori
            'G' => 12,  // Brand
            'H' => 15,  // Sub Kategori
            'I' => 10,  // Premiere
            'J' => 20,  // Serial Numbers
            'K' => 35,  // Error Message
            'L' => 40,  // Saran Perbaikan
        ];
    }
}
