<?php

namespace App\Filament\Imports;

use App\Models\ProductSpecification;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Filament\Notifications\Notification;


class ProductSpecificationImporter extends Importer
{
    protected static ?string $model = ProductSpecification::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('product')
                ->requiredMapping()
                ->relationship()
                ->rules(['required']),
            ImportColumn::make('name')
                ->requiredMapping()
                ->rules(['required', 'string']),
        ];;
    }

    public function resolveRecord(): ?ProductSpecification
    {
        // return ProductPhoto::firstOrNew([
        //     // Update existing records, matching them by `$this->data['column_name']`
        //     'email' => $this->data['email'],
        // ]);

        return new ProductSpecification();
    }


    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your product specification import has completed and ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
