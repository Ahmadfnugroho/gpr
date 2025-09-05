<?php

namespace App\Filament\Imports;

use App\Models\Product;
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
            ImportColumn::make('product_id')
                ->label('Product ID')
                ->requiredMapping()
                ->rules(['required']),
            ImportColumn::make('photo')
                ->label('Photo')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255']),
        ];
    }

    public function resolveRecord(): ?ProductPhoto
    {
        $product = Product::where('id', $this->data['product_id'])->first();

        if (!$product) {
            return null;
        }

        return ProductPhoto::firstOrNew([
            'product_id' => $product->id,
            'photo' => $this->data['photo'],
        ]);
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
