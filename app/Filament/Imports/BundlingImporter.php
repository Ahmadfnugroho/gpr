<?php

namespace App\Filament\Imports;

use App\Models\Bundling;
use App\Models\Product;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class BundlingImporter extends Importer
{
    protected static ?string $model = Bundling::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('name')
                ->requiredMapping()
                ->rules(['required']),

            ImportColumn::make('price')
                ->requiredMapping()
                ->numeric()
                ->rules(['required']),

            ImportColumn::make('product_name')
                ->requiredMapping()
                ->rules(['required']),

            ImportColumn::make('quantity')
                ->requiredMapping()
                ->numeric()
        ];
    }

    public function fillRecord(): void
    {
        $this->record->name = $this->data['name'] ?? $this->record->name;
        $this->record->price = $this->data['price'] ?? $this->record->price;
    }

    public function resolveRecord(): ?Bundling
    {
        // Cari atau buat bundling berdasarkan nama
        $bundling = Bundling::firstOrNew(['name' => $this->data['name']]);
        $bundling->price = $this->data['price'] ?? $bundling->price;
        $bundling->save();


        if (!empty($this->data['product_name']) && !empty($this->data['quantity'])) {
            $productName = trim($this->data['product_name']);
            $quantity = (int)$this->data['quantity'];

            $product = Product::where('name', $productName)->first();

            if ($product) {
                // Simpan produk dengan quantity ke pivot table
                $bundling->products()->syncWithoutDetaching([
                    $product->id => ['quantity' => $quantity]
                ]);
            }
        }

        return $bundling;
    }


    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Impor bundling selesai. ' . number_format($import->successful_rows) . ' baris berhasil diimpor.';
        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' baris gagal diimpor.';
        }

        return $body;
    }
}
