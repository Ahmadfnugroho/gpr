<?php

namespace App\Filament\Imports;

use App\Models\Bundling;
use App\Models\Product;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class BundlingImporter extends Importer
{
    protected static ?string $model = Bundling::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('name')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255']),

            ImportColumn::make('price')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'integer', 'min:0']),

            ImportColumn::make('product_name')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255']),

            ImportColumn::make('quantity')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'integer', 'min:1'])
        ];
    }

    public function fillRecord(): void
    {
        $this->record->name = $this->data['name'] ?? $this->record->name;
        $this->record->price = $this->data['price'] ?? $this->record->price;
    }

    public function resolveRecord(): ?Bundling
    {
        try {
            DB::beginTransaction();

            // Validate required data
            if (empty($this->data['name'])) {
                throw new Exception('Bundling name is required');
            }

            if (empty($this->data['price'])) {
                throw new Exception('Bundling price is required');
            }

            // Find or create bundling
            $bundling = Bundling::firstOrNew(['name' => trim($this->data['name'])]);
            $bundling->price = (int)$this->data['price'];
            $bundling->save();

            Log::info("Processing bundling: {$bundling->name} (ID: {$bundling->id})");

            // Handle product association
            if (!empty($this->data['product_name']) && !empty($this->data['quantity'])) {
                $productName = trim($this->data['product_name']);
                $quantity = (int)$this->data['quantity'];

                // Case-insensitive product search
                $product = Product::whereRaw('LOWER(name) = LOWER(?)', [$productName])->first();

                if ($product) {
                    // Check if this product is already attached to this bundling
                    $existingPivot = $bundling->products()->where('product_id', $product->id)->first();
                    
                    if ($existingPivot) {
                        // Update quantity if product already exists
                        $bundling->products()->updateExistingPivot($product->id, ['quantity' => $quantity]);
                        Log::info("Updated product '{$product->name}' quantity to {$quantity} in bundling '{$bundling->name}'");
                    } else {
                        // Attach new product
                        $bundling->products()->attach($product->id, ['quantity' => $quantity]);
                        Log::info("Added product '{$product->name}' with quantity {$quantity} to bundling '{$bundling->name}'");
                    }
                } else {
                    $availableProducts = Product::pluck('name')->implode(', ');
                    Log::warning("Product not found: '{$productName}'. Available products: {$availableProducts}");
                    throw new Exception("Product '{$productName}' not found. Please check product name.");
                }
            }

            DB::commit();
            return $bundling;

        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Bundling import failed: " . $e->getMessage(), [
                'data' => $this->data,
                'line' => $this->getRecord()?->getKey() ?? 'unknown'
            ]);
            
            // Re-throw to make import fail visibly
            throw new Exception("Import failed for bundling '{$this->data['name']}': " . $e->getMessage());
        }
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
