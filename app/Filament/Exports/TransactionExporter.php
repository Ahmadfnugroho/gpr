<?php

namespace App\Filament\Exports;

use App\Models\Transaction;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class TransactionExporter extends Exporter
{
    protected static ?string $model = Transaction::class;
    
    public function __construct($export, $columnMap, $options)
    {
        \Log::info('🚨 TransactionExporter CONSTRUCTOR CALLED!', [
            'timestamp' => date('Y-m-d H:i:s'),
            'export_id' => $export->id ?? 'NULL',
            'export_file' => $export->file_name ?? 'NULL',
            'options' => $options
        ]);
        
        parent::__construct($export, $columnMap, $options);
    }

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID'),
            
            ExportColumn::make('booking_transaction_id')
                ->label('Booking ID'),
            
            ExportColumn::make('customer.name')
                ->label('Customer Name'),
            
            ExportColumn::make('customer.email')
                ->label('Customer Email'),
                
            ExportColumn::make('booking_status')
                ->label('Status'),
                
            ExportColumn::make('start_date')
                ->label('Start Date'),
                
            ExportColumn::make('end_date')
                ->label('End Date'),
                
            ExportColumn::make('duration')
                ->label('Duration (Days)'),
                
            ExportColumn::make('grand_total')
                ->label('Grand Total'),
                
            ExportColumn::make('down_payment')
                ->label('Down Payment'),
                
            ExportColumn::make('remaining_payment')
                ->label('Remaining Payment'),
                
            ExportColumn::make('promo.name')
                ->label('Promo'),
                
            ExportColumn::make('additional_services')
                ->label('Additional Services')
                ->formatStateUsing(function ($state) {
                    if (empty($state)) {
                        return '';
                    }
                    
                    if (is_array($state)) {
                        return json_encode($state, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    }
                    
                    return $state;
                }),
                
            ExportColumn::make('cancellation_fee')
                ->label('Cancellation Fee'),
                
            ExportColumn::make('note')
                ->label('Note'),
                
            ExportColumn::make('created_at')
                ->label('Created At'),
                
            ExportColumn::make('updated_at')
                ->label('Updated At'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Transaction export completed successfully with ' . number_format($export->successful_rows) . ' row(s).';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' row(s) failed to export.';
        }

        return $body;
    }
}
