<?php

namespace App\Filament\Exports;

use App\Models\Promo;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class PromoExporter extends Exporter
{
    protected static ?string $model = Promo::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID'),
            ExportColumn::make('name'),
            ExportColumn::make('code'),
            ExportColumn::make('description'),
            ExportColumn::make('type'),
            ExportColumn::make('value'),
            ExportColumn::make('min_transaction'),
            ExportColumn::make('max_discount'),
            ExportColumn::make('valid_from'),
            ExportColumn::make('valid_until'),
            ExportColumn::make('active'),
            ExportColumn::make('created_at'),
            ExportColumn::make('updated_at'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your promo export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
