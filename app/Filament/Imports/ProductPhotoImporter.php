<?php

namespace App\Filament\Imports;

use App\Models\ProductPhoto;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class ProductPhotoImporter extends Importer
{
    protected static ?string $model = ProductPhoto::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('product')
                ->requiredMapping()
                ->relationship()
                ->rules(['required']),
            ImportColumn::make('photo')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
        ];
    }

    public function resolveRecord(): ?ProductPhoto
    {
        // return ProductPhoto::firstOrNew([
        //     // Update existing records, matching them by `$this->data['column_name']`
        //     'email' => $this->data['email'],
        // ]);

        return new ProductPhoto();
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your product photo import has completed and ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
