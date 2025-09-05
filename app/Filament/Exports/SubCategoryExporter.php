<?php

namespace App\Filament\Exports;

use App\Models\SubCategory;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class SubCategoryExporter extends Exporter
{
    protected static ?string $model = SubCategory::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID'),

            ExportColumn::make('name')
                ->label('Nama Sub Kategori'),

            ExportColumn::make('category.name')
                ->label('Kategori'),

            ExportColumn::make('photo')
                ->label('Photo URL'),

            ExportColumn::make('slug')
                ->label('Slug'),

            ExportColumn::make('products_count')
                ->label('Jumlah Produk')
                ->counts('products'),

            ExportColumn::make('created_at')
                ->label('Tanggal Dibuat'),

            ExportColumn::make('updated_at')
                ->label('Terakhir Update'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your sub category export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
