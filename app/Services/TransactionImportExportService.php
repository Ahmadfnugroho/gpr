<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\Customer;
use App\Models\User;
use App\Models\Promo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TransactionImportExportService
{
    /**
     * Export transactions to Excel
     */
    public function exportTransactions(array $transactionIds = null): string
    {
        $export = new TransactionExport($transactionIds);
        $filename = 'transactions_export_' . date('Y-m-d_H-i-s') . '.xlsx';
        
        Excel::store($export, $filename, 'public');
        
        return Storage::disk('public')->path($filename);
    }

    /**
     * Generate Excel template for reference (read-only)
     * Note: Transaction import is not recommended due to complexity and business logic
     */
    public function generateTemplate(): string
    {
        $template = new TransactionTemplate();
        $filename = 'transaction_reference_template.xlsx';
        
        Excel::store($template, $filename, 'public');
        
        return Storage::disk('public')->path($filename);
    }
}

/**
 * Transaction Export Class
 */
class TransactionExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $transactionIds;

    public function __construct(array $transactionIds = null)
    {
        $this->transactionIds = $transactionIds;
    }

    public function collection()
    {
        $query = Transaction::with([
            'customer', 
            'user', 
            'promo', 
            'detailTransactions.product',
            'detailTransactions.bundling'
        ]);
        
        if ($this->transactionIds) {
            $query->whereIn('id', $this->transactionIds);
        }
        
        return $query->orderBy('created_at', 'desc')->get();
    }

    public function headings(): array
    {
        return [
            'ID Transaksi',
            'Booking ID',
            'Customer',
            'Customer Email',
            'Staff/User',
            'Status',
            'Tanggal Mulai',
            'Tanggal Selesai',
            'Durasi (Hari)',
            'Produk/Bundling',
            'Grand Total',
            'DP',
            'Sisa Bayar',
            'Promo',
            'Biaya Tambahan 1',
            'Jumlah Biaya Tambahan 1',
            'Biaya Tambahan 2',
            'Jumlah Biaya Tambahan 2',
            'Biaya Tambahan 3',
            'Jumlah Biaya Tambahan 3',
            'Biaya Cancel',
            'Catatan',
            'Tanggal Dibuat',
            'Terakhir Update'
        ];
    }

    public function map($transaction): array
    {
        // Format products/bundlings
        $items = [];
        foreach ($transaction->detailTransactions as $detail) {
            if ($detail->product) {
                $items[] = $detail->product->name . ' (Qty: ' . $detail->quantity . ')';
            }
            if ($detail->bundling) {
                $items[] = $detail->bundling->name . ' (Qty: ' . $detail->quantity . ')';
            }
        }
        $itemsText = implode(', ', $items);

        // Format status
        $statusLabels = [
            'booking' => 'Booking',
            'paid' => 'Paid',
            'on_rented' => 'On Rented',
            'done' => 'Done',
            'cancel' => 'Cancel'
        ];

        return [
            $transaction->id,
            $transaction->booking_transaction_id,
            $transaction->customer?->name ?? '-',
            $transaction->customer?->email ?? '-',
            $transaction->user?->name ?? '-',
            $statusLabels[$transaction->booking_status] ?? $transaction->booking_status,
            $transaction->start_date?->format('Y-m-d H:i'),
            $transaction->end_date?->format('Y-m-d H:i'),
            $transaction->duration,
            $itemsText ?: '-',
            'Rp ' . number_format($transaction->grand_total ?? 0, 0, ',', '.'),
            'Rp ' . number_format($transaction->down_payment ?? 0, 0, ',', '.'),
            'Rp ' . number_format($transaction->remaining_payment ?? 0, 0, ',', '.'),
            $transaction->promo?->code ?? '-',
            $transaction->additional_fee_1_name ?? '-',
            $transaction->additional_fee_1_amount ? 'Rp ' . number_format($transaction->additional_fee_1_amount, 0, ',', '.') : '-',
            $transaction->additional_fee_2_name ?? '-',
            $transaction->additional_fee_2_amount ? 'Rp ' . number_format($transaction->additional_fee_2_amount, 0, ',', '.') : '-',
            $transaction->additional_fee_3_name ?? '-',
            $transaction->additional_fee_3_amount ? 'Rp ' . number_format($transaction->additional_fee_3_amount, 0, ',', '.') : '-',
            $transaction->cancellation_fee ? 'Rp ' . number_format($transaction->cancellation_fee, 0, ',', '.') : '-',
            $transaction->note ?? '-',
            $transaction->created_at?->format('Y-m-d H:i:s'),
            $transaction->updated_at?->format('Y-m-d H:i:s')
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold text
            1 => ['font' => ['bold' => true]],
            
            // Set auto width for all columns
            'A:X' => ['alignment' => ['wrapText' => true]]
        ];
    }
}

/**
 * Transaction Template Class (For Reference Only)
 */
class TransactionTemplate implements FromCollection, WithHeadings, WithStyles
{
    public function collection()
    {
        // Return empty collection - this is just for reference
        return collect([]);
    }

    public function headings(): array
    {
        return [
            'IMPORTANT NOTE: This is a REFERENCE template only',
            'Transaction import is NOT supported due to business complexity',
            'Use the Transaction form in the admin panel instead',
            '',
            'Fields included in export:',
            'ID, Booking ID, Customer, Status, Dates, Products, Amounts, etc.',
            '',
            'For data import needs, please contact system administrator'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Set header row style
        $sheet->getStyle('A1:H8')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF']
            ],
            'fill' => [
                'fillType' => 'solid',
                'startColor' => ['rgb' => 'FF6B6B']
            ]
        ]);
     
        // Auto-size columns
        foreach (range('A', 'H') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
     
        return [];
    }
}
