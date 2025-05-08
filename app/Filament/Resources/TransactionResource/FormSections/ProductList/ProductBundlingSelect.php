<?php

namespace App\Filament\Resources\TransactionResource\FormSections\ProductList;

use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use App\Models\Product;
use App\Models\Bundling;
use App\Models\DetailTransaction;

class ProductBundlingSelect
{
    public static function make(): Select
    {
        return Select::make('product_id')
            ->label('Produk/Bundling')
            ->searchable()
            ->preload()
            ->reactive()
            ->required()
            ->options(function () {
                $products = Product::query()
                    ->select('id', 'name')
                    ->get()
                    ->mapWithKeys(fn($p) => ["produk-{$p->id}" => $p->name]);

                $bundlings = Bundling::query()
                    ->select('id', 'name')
                    ->get()
                    ->mapWithKeys(fn($b) => ["bundling-{$b->id}" => $b->name]);

                return $products->merge($bundlings);
            })
            ->getOptionLabelUsing(function ($value) {
                if (str_contains($value, '-')) {
                    $parts = explode('-', $value);
                    if ($parts[0] === 'produk') {
                        return Product::find($parts[1])?->name;
                    } elseif ($parts[0] === 'bundling') {
                        return Bundling::find($parts[1])?->name;
                    }
                }
                return $value;
            })
            ->default(function ($record) {
                if ($record?->product_id) return "produk-{$record->product_id}";
                if ($record?->bundling_id) return "bundling-{$record->bundling_id}";
                return null;
            })
            ->afterStateUpdated(function ($state, $set, $get) {
                $productId = null;
                $bundlingId = null;
                $productName = '-';
                $availableQuantity = 0;

                if (str_starts_with($state, 'produk-')) {
                    $productId = substr($state, 7);
                    $product = Product::find($productId);
                    $productName = $product->name ?? '-';

                    // Calculate product availability
                    $rented = DetailTransaction::where('product_id', $productId)
                        ->whereNotIn('id', [$get('id')])
                        ->whereHas('transaction', function ($query) use ($get) {
                            $query->whereIn('booking_status', ['rented', 'paid', 'pending'])
                                ->where('start_date', '<=', $get('../../end_date'))
                                ->where('end_date', '>=', $get('../../start_date'));
                        })
                        ->sum('quantity');

                    $availableQuantity = max(($product->quantity ?? 0) - $rented, 0);
                } elseif (str_starts_with($state, 'bundling-')) {
                    $bundlingId = substr($state, 9);
                    $bundling = Bundling::with('products')->find($bundlingId);
                    $productName = $bundling->name ?? '-';

                    // Calculate bundling availability
                    $availableQuantities = [];
                    foreach ($bundling->products ?? [] as $product) {
                        $rented = DetailTransaction::where('product_id', $product->id)
                            ->whereNotIn('id', [$get('id')])
                            ->whereHas('transaction', function ($query) use ($get) {
                                $query->whereIn('booking_status', ['rented', 'paid', 'pending'])
                                    ->where('start_date', '<=', $get('../../end_date'))
                                    ->where('end_date', '>=', $get('../../start_date'));
                            })
                            ->sum('quantity');

                        $availableQuantities[] = max($product->quantity - $rented, 0);
                    }
                    $availableQuantity = min($availableQuantities);
                    $set('is_bundling', true);
                } else {
                    $set('is_bundling', false);
                    if (str_starts_with($state, 'produk-')) {
                        $productId = (int) substr($state, 7);
                        $product = \App\Models\Product::find($productId);

                        if (!$product) {
                            return 1; // Avoid error
                        }

                        $rentedQuantity = \App\Models\DetailTransaction::where('product_id', $productId)
                            ->whereHas('transaction', function ($query) use ($get) {
                                $query->whereIn('booking_status', ['rented', 'paid', 'pending'])
                                    ->where('start_date', '<=', $get('../../end_date'))
                                    ->where('end_date', '>=', $get('../../start_date'));
                            })
                            ->sum('quantity');

                        $availableQuantity = min($product->quantity - $rentedQuantity, 0);
                    }
                }

                // Set form values
                $set('product_id', $productId);
                $set('bundling_id', $bundlingId);
                $set('product_name_display', $productName);

                // Notify if stock unavailable
                if ($availableQuantity <= 0) {
                    Notification::make()
                        ->danger()
                        ->title('Stok Tidak Tersedia')
                        ->send();
                }
            })
            ->columnStart(1);
    }
}
