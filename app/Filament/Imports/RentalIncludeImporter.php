<?php

namespace App\Filament\Imports;

use App\Models\RentalInclude;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class RentalIncludeImporter extends Importer
{
    protected static ?string $model = RentalInclude::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('product_id') // Menggunakan ID alih-alih nama
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'integer', 'exists:products,id']), // Validasi ID produk
            ImportColumn::make('include_product_id') // ID produk yang disertakan
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'integer', 'exists:products,id']),
            ImportColumn::make('quantity')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'integer']) // Validasi ID produk
        ];
    }

    public function resolveRecord(): ?RentalInclude
    {
        // Memastikan menemukan atau membuat record berdasarkan ID
        return RentalInclude::firstOrNew([
            'product_id' => $this->data['product_id'],
            'include_product_id' => $this->data['include_product_id'],
        ]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your rental include import has completed and ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
