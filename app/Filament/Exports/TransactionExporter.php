<?php

namespace App\Filament\Exports;

use App\Models\Transaction;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class TransactionExporter extends Exporter
{
    protected static ?string $model = Transaction::class;

    // Override to generate raw data with one row per product/bundling
    public function getRecords(): \Illuminate\Support\Collection
    {
        $transactions = parent::getRecords();
        $expandedRows = collect();

        foreach ($transactions as $transaction) {
            // Load relationships
            $transaction->load([
                'customer',
                'customer.customerPhoneNumbers',
                'detailTransactions.product',
                'detailTransactions.bundling',
                'promo'
            ]);

            // Get common transaction data
            $baseData = $this->getBaseTransactionData($transaction);

            // If no detail transactions, create one row with empty product/bundling
            if ($transaction->detailTransactions->isEmpty()) {
                $baseData['product_bundling'] = '';
                $expandedRows->push($baseData);
            } else {
                // Create separate row for each product/bundling
                foreach ($transaction->detailTransactions as $detail) {
                    $rowData = $baseData;
                    
                    if ($detail->bundling_id && $detail->bundling) {
                        $rowData['product_bundling'] = $detail->bundling->name . ' (Bundle)';
                    } elseif ($detail->product_id && $detail->product) {
                        $rowData['product_bundling'] = $detail->product->name;
                    } else {
                        $rowData['product_bundling'] = 'Unknown Item';
                    }
                    
                    $expandedRows->push((object) $rowData);
                }
            }
        }

        return $expandedRows;
    }

    private function getBaseTransactionData(Transaction $transaction): array
    {
        // Parse additional services
        $additionalServices = $this->parseAdditionalServices($transaction);
        
        return [
            'id' => $transaction->id,
            'booking_transaction_id' => $transaction->booking_transaction_id,
            'customer_name' => $transaction->customer->name ?? 'N/A',
            'customer_email' => $transaction->customer->email ?? 'N/A',
            'staff_user' => '-', // You can modify this if you have user/staff data
            'booking_status' => ucfirst($transaction->booking_status),
            'start_date' => $transaction->start_date ? $transaction->start_date->format('Y-m-d H:i') : '',
            'end_date' => $transaction->end_date ? $transaction->end_date->format('Y-m-d H:i') : '',
            'duration' => $transaction->duration,
            'grand_total' => $transaction->grand_total,
            'down_payment' => $transaction->down_payment,
            'remaining_payment' => $transaction->remaining_payment,
            'promo' => $transaction->promo->name ?? '',
            'additional_fee_1_name' => $additionalServices['fee_1']['name'] ?? '',
            'additional_fee_1_amount' => $additionalServices['fee_1']['amount'] ?? '',
            'additional_fee_2_name' => $additionalServices['fee_2']['name'] ?? '',
            'additional_fee_2_amount' => $additionalServices['fee_2']['amount'] ?? '',
            'additional_fee_3_name' => $additionalServices['fee_3']['name'] ?? '',
            'additional_fee_3_amount' => $additionalServices['fee_3']['amount'] ?? '',
            'cancellation_fee' => $transaction->booking_status === 'cancel' ? $transaction->cancellation_fee : 0,
            'note' => $transaction->note ?? '',
            'created_at' => $transaction->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $transaction->updated_at->format('Y-m-d H:i:s'),
        ];
    }

    private function parseAdditionalServices(Transaction $transaction): array
    {
        $services = ['fee_1' => [], 'fee_2' => [], 'fee_3' => []];
        $index = 1;

        // Parse new additional_services structure
        if ($transaction->additional_services && is_array($transaction->additional_services)) {
            foreach ($transaction->additional_services as $service) {
                if (is_array($service) && isset($service['name'], $service['amount']) && $index <= 3) {
                    $services["fee_$index"] = [
                        'name' => $service['name'],
                        'amount' => $service['amount']
                    ];
                    $index++;
                }
            }
        }

        // Parse legacy additional_fee structure (if no new structure)
        if ($index === 1) {
            if ($transaction->additional_fee_1_name && $transaction->additional_fee_1_amount) {
                $services['fee_1'] = [
                    'name' => $transaction->additional_fee_1_name,
                    'amount' => $transaction->additional_fee_1_amount
                ];
            }
            if ($transaction->additional_fee_2_name && $transaction->additional_fee_2_amount) {
                $services['fee_2'] = [
                    'name' => $transaction->additional_fee_2_name,
                    'amount' => $transaction->additional_fee_2_amount
                ];
            }
            if ($transaction->additional_fee_3_name && $transaction->additional_fee_3_amount) {
                $services['fee_3'] = [
                    'name' => $transaction->additional_fee_3_name,
                    'amount' => $transaction->additional_fee_3_amount
                ];
            }
        }

        return $services;
    }

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID Transaksi'),
            ExportColumn::make('booking_transaction_id')
                ->label('Booking ID'),
            ExportColumn::make('customer_name')
                ->label('Customer'),
            ExportColumn::make('customer_email')
                ->label('Customer Email'),
            ExportColumn::make('staff_user')
                ->label('Staff/User'),
            ExportColumn::make('booking_status')
                ->label('Status'),
            ExportColumn::make('start_date')
                ->label('Tanggal Mulai'),
            ExportColumn::make('end_date')
                ->label('Tanggal Selesai'),
            ExportColumn::make('duration')
                ->label('Durasi (Hari)'),
            ExportColumn::make('product_bundling')
                ->label('Produk/Bundling'),
            ExportColumn::make('grand_total')
                ->label('Grand Total'),
            ExportColumn::make('down_payment')
                ->label('DP'),
            ExportColumn::make('remaining_payment')
                ->label('Sisa Bayar'),
            ExportColumn::make('promo')
                ->label('Promo'),
            ExportColumn::make('additional_fee_1_name')
                ->label('Biaya Tambahan 1'),
            ExportColumn::make('additional_fee_1_amount')
                ->label('Jumlah Biaya Tambahan 1'),
            ExportColumn::make('additional_fee_2_name')
                ->label('Biaya Tambahan 2'),
            ExportColumn::make('additional_fee_2_amount')
                ->label('Jumlah Biaya Tambahan 2'),
            ExportColumn::make('additional_fee_3_name')
                ->label('Biaya Tambahan 3'),
            ExportColumn::make('additional_fee_3_amount')
                ->label('Jumlah Biaya Tambahan 3'),
            ExportColumn::make('cancellation_fee')
                ->label('Biaya Cancel'),
            ExportColumn::make('note')
                ->label('Catatan'),
            ExportColumn::make('created_at')
                ->label('Tanggal Dibuat'),
            ExportColumn::make('updated_at')
                ->label('Terakhir Update'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Export transaksi telah selesai dengan ' . number_format($export->successful_rows) . ' baris data.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' baris gagal di-export.';
        }

        return $body;
    }
}
