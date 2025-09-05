<?php

namespace App\Filament\Exports;

use App\Models\RentalInclude;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class RentalIncludeExporter extends Exporter
{
    protected static ?string $model = RentalInclude::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID'),

            ExportColumn::make('product.name')
                ->label('Nama Produk Utama'),

            ExportColumn::make('includedProduct.name')
                ->label('Nama Produk Include'),

            ExportColumn::make('quantity')
                ->label('Jumlah'),

            ExportColumn::make('created_at')
                ->label('Tanggal Dibuat'),

            ExportColumn::make('updated_at')
                ->label('Terakhir Update'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your rental include export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
