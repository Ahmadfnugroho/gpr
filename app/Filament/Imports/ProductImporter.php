<?php

namespace App\Filament\Imports;

use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\SubCategory;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;


class ProductImporter extends Importer
{
    protected static ?string $model = Product::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('name')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('price')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'integer']),
            ImportColumn::make('thumbnail')
                ->rules(['nullable', 'max:255']),
            ImportColumn::make('status')
                ->requiredMapping()
                ->rules(['required']),
            ImportColumn::make('category')
                ->rules(['nullable']),
            ImportColumn::make('brand')
                ->rules(['nullable']),
            ImportColumn::make('sub_category')
                ->rules(['nullable']),
            ImportColumn::make('premiere')
                ->rules(['nullable', 'boolean']),
            ImportColumn::make('serial_numbers')
                ->rules(['nullable', 'string']),
        ];
    }

    public function resolveRecord(): ?Product
    {
        try {
            DB::beginTransaction();

            // Ambil atau buat produk berdasarkan name
            $product = Product::where('name', $this->data['name'] ?? '')->first();
            if (!$product) {
                $product = new Product();
            }

            // Handle premiere (default false)
            if (!isset($this->data['premiere']) || is_null($this->data['premiere'])) {
                $this->data['premiere'] = false;
            } else {
                $this->data['premiere'] = filter_var($this->data['premiere'], FILTER_VALIDATE_BOOLEAN);
            }

            // Simpan serial numbers untuk nanti
            $serialNumbers = $this->data['serial_numbers'] ?? null;
            unset($this->data['serial_numbers']);

            // Mapping category, brand, sub_category ke ID
            $category = Category::where('name', $this->data['category'] ?? null)->first();
            $brand = Brand::where('name', $this->data['brand'] ?? null)->first();
            $subCategory = SubCategory::where('name', $this->data['sub_category'] ?? null)->first();

            // Hapus kolom yang bukan field tabel
            unset($this->data['category']);
            unset($this->data['brand']);
            unset($this->data['sub_category']);

            // Set ID
            $this->data['category_id'] = $category ? $category->id : null;
            $this->data['brand_id'] = $brand ? $brand->id : null;
            $this->data['sub_category_id'] = $subCategory ? $subCategory->id : null;

            // Isi atribut produk
            $product->fill($this->data);
            $product->save();

            // Simpan serial numbers
            if (!empty($serialNumbers)) {
                $serialNumbersArray = array_map('trim', explode(',', $serialNumbers));
                foreach ($serialNumbersArray as $serial) {
                    if (!$product->items()->where('serial_number', $serial)->exists()) {
                        $product->items()->create([
                            'serial_number' => $serial,
                            'is_available' => true,
                        ]);
                    }
                }
            }

            DB::commit();
            return $product;
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception("Import gagal: " . $e->getMessage());
        }
    }




    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Impor produk selesai. ' . number_format($import->successful_rows) . ' data berhasil diimpor.';
        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' data gagal diimpor.';
        }
        return $body;
    }
}
