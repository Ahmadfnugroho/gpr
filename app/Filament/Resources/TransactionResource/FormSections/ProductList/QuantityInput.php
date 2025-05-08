<?php

namespace App\Filament\Resources\TransactionResource\FormSections\ProductList;

use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use App\Models\DetailTransaction;
use App\Models\Bundling;
use App\Models\Product;
use App\Models\ProductItem;
use Carbon\Carbon;
use Filament\Forms\Get;

class QuantityInput
{
    public static function make(): TextInput
    {
        return TextInput::make('quantity')
            ->required()
            ->numeric()
            ->reactive()
            ->default(1)
            ->minValue(1)
            ->maxValue(function (Get $get) {
                $customId = (int) $get('is_bundling') === 1 ? $get('bundling_id') : $get('product_id');
                $startDate = Carbon::parse($get('../../start_date'));
                $endDate = Carbon::parse($get('../../end_date'));

                if (!$customId) {
                    return 0;
                }

                $includedStatuses = ['rented', 'paid', 'pending'];
                $availableQuantity = 0;

                if ((int) $get('is_bundling') === 1) {
                    $bundling = Bundling::with('products.items')->find($customId);
                    $availableQuantities = [];

                    foreach ($bundling->products as $product) {
                        $availableProductItems = $product->items
                            ->where('is_available', true)
                            ->filter(function ($item) use ($startDate, $endDate, $includedStatuses) {
                                return !$item->productTransactions()
                                    ->whereHas('transaction', function ($query) use ($startDate, $endDate, $includedStatuses) {
                                        $query->whereIn('booking_status', $includedStatuses)
                                            ->where('start_date', '<=', $endDate)
                                            ->where('end_date', '>=', $startDate);
                                    })
                                    ->exists();
                            });

                        $availableQuantities[] = $availableProductItems->count();
                    }

                    $availableQuantity = min($availableQuantities);
                } else {
                    $availableProductItems = ProductItem::where('product_id', $customId)
                        ->where('is_available', true)
                        ->whereDoesntHave('productTransactions.transaction', function ($query) use ($startDate, $endDate, $includedStatuses) {
                            $query->whereIn('booking_status', $includedStatuses)
                                ->where('start_date', '<=', $endDate)
                                ->where('end_date', '>=', $startDate);
                        })
                        ->get();

                    $availableQuantity = $availableProductItems->count();
                }

                if ($availableQuantity <= 0) {
                    Notification::make()
                        ->danger()
                        ->title('Produk tidak tersedia')
                        ->body('Produk yang Anda pilih tidak tersedia dalam tanggal yang dipilih.')
                        ->send();

                    return 1;
                }

                return $availableQuantity;
            })
            ->columnSpan(1);
    }
}
