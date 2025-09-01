<?php

namespace App\Filament\Exports;

use App\Models\Transaction;
use App\Models\ExportSetting;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class TransactionExporter extends Exporter
{
    protected static ?string $model = Transaction::class;

    public static function getColumns(): array
    {
        $settings = ExportSetting::getSettings('TransactionResource');
        $includedColumns = $settings['included_columns'] ?? [];
        $excludedColumns = $settings['excluded_columns'] ?? [];
        
        $allColumns = [
            'booking_transaction_id' => ExportColumn::make('booking_transaction_id')
                ->label('Transaction ID'),
            'customer_name' => ExportColumn::make('customer.name')
                ->label('Customer Name'),
            'customer_email' => ExportColumn::make('customer.email')
                ->label('Customer Email'),
            'customer_phone' => ExportColumn::make('customer_phone')
                ->label('Customer Phone')
                ->state(function (Transaction $record): string {
                    return $record->customer->customerPhoneNumbers->first()?->phone_number ?? 'N/A';
                }),
            'product_info' => ExportColumn::make('product_info')
                ->label('Products/Bundles')
                ->state(function (Transaction $record): string {
                    $products = [];
                    foreach ($record->detailTransactions as $detail) {
                        if ($detail->bundling_id && $detail->bundling) {
                            $products[] = $detail->bundling->name . ' (Bundle)';
                        } elseif ($detail->product_id && $detail->product) {
                            $products[] = $detail->product->name;
                        }
                    }
                    return implode(', ', array_unique($products));
                }),
            'detail_transactions' => ExportColumn::make('detail_transactions')
                ->label('Detail Transactions')
                ->state(function (Transaction $record): string {
                    $details = [];
                    foreach ($record->detailTransactions as $detail) {
                        $itemName = '';
                        if ($detail->bundling_id && $detail->bundling) {
                            $itemName = $detail->bundling->name . ' (Bundle)';
                        } elseif ($detail->product_id && $detail->product) {
                            $itemName = $detail->product->name;
                        }
                        
                        if ($itemName) {
                            $details[] = $itemName . ' x' . $detail->quantity;
                        }
                    }
                    return implode('; ', $details);
                }),
            'serial_numbers' => ExportColumn::make('serial_numbers')
                ->label('Serial Numbers')
                ->state(function (Transaction $record): string {
                    $serialNumbers = [];
                    foreach ($record->detailTransactions as $detail) {
                        foreach ($detail->productItems as $item) {
                            $serialNumbers[] = $item->serial_number;
                        }
                    }
                    return implode(', ', $serialNumbers);
                }),
            'start_date' => ExportColumn::make('start_date')
                ->label('Start Date')
                ->state(fn (Transaction $record): string => $record->start_date ? $record->start_date->format('d M Y, H:i') : ''),
            'end_date' => ExportColumn::make('end_date')
                ->label('End Date')
                ->state(fn (Transaction $record): string => $record->end_date ? $record->end_date->format('d M Y, H:i') : ''),
            'duration' => ExportColumn::make('duration')
                ->label('Duration (Days)'),
            'grand_total' => ExportColumn::make('grand_total')
                ->label('Grand Total')
                ->state(fn (Transaction $record): string => 'Rp' . number_format($record->grand_total, 0, ',', '.')),
            'down_payment' => ExportColumn::make('down_payment')
                ->label('Down Payment')
                ->state(fn (Transaction $record): string => 'Rp' . number_format($record->down_payment, 0, ',', '.')),
            'remaining_payment' => ExportColumn::make('remaining_payment')
                ->label('Remaining Payment')
                ->state(fn (Transaction $record): string => $record->remaining_payment == 0 ? 'LUNAS' : 'Rp' . number_format($record->remaining_payment, 0, ',', '.')),
            'booking_status' => ExportColumn::make('booking_status')
                ->label('Status'),
            'promo_applied' => ExportColumn::make('promo.name')
                ->label('Promo Applied')
                ->default('None'),
            'additional_services_info' => ExportColumn::make('additional_services_info')
                ->label('Additional Services')
                ->state(function (Transaction $record): string {
                    $services = [];
                    
                    // New additional_services structure
                    if ($record->additional_services && is_array($record->additional_services)) {
                        foreach ($record->additional_services as $service) {
                            if (is_array($service) && isset($service['name'], $service['amount'])) {
                                $services[] = $service['name'] . ': Rp' . number_format((int)$service['amount'], 0, ',', '.');
                            }
                        }
                    }
                    
                    // Legacy additional_fee structure for backward compatibility
                    if ($record->additional_fee_1_name && $record->additional_fee_1_amount) {
                        $services[] = $record->additional_fee_1_name . ': Rp' . number_format($record->additional_fee_1_amount, 0, ',', '.');
                    }
                    if ($record->additional_fee_2_name && $record->additional_fee_2_amount) {
                        $services[] = $record->additional_fee_2_name . ': Rp' . number_format($record->additional_fee_2_amount, 0, ',', '.');
                    }
                    if ($record->additional_fee_3_name && $record->additional_fee_3_amount) {
                        $services[] = $record->additional_fee_3_name . ': Rp' . number_format($record->additional_fee_3_amount, 0, ',', '.');
                    }
                    
                    return empty($services) ? 'None' : implode(', ', $services);
                }),
            'cancellation_fee' => ExportColumn::make('cancellation_fee')
                ->label('Cancellation Fee')
                ->state(function (Transaction $record): string {
                    if ($record->booking_status === 'cancel' && $record->cancellation_fee && $record->cancellation_fee > 0) {
                        return 'Rp' . number_format($record->cancellation_fee, 0, ',', '.');
                    }
                    return 'None';
                }),
            'note' => ExportColumn::make('note')
                ->label('Notes')
                ->default(''),
            'created_at' => ExportColumn::make('created_at')
                ->label('Created At')
                ->state(fn (Transaction $record): string => $record->created_at ? $record->created_at->format('d M Y, H:i') : ''),
        ];

        // Filter columns based on settings
        $filteredColumns = [];
        foreach ($allColumns as $key => $column) {
            // Include column if it's in included_columns and not in excluded_columns
            if (in_array($key, $includedColumns) && !in_array($key, $excludedColumns)) {
                $filteredColumns[] = $column;
            }
        }

        return $filteredColumns;
    }


    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your transaction export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
