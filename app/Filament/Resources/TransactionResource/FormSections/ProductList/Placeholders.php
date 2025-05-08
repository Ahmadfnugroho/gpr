<?php

namespace App\Filament\Resources\TransactionResource\FormSections\ProductList;

use Filament\Forms\Components\Placeholder;
use Filament\Forms\Get;
use Filament\Forms\Set;
use App\Models\DetailTransaction;
use App\Models\Bundling;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\HtmlString;

class Placeholders
{
    public static function productNameDisplay(): Placeholder
    {
        return Placeholder::make('product_name_display')
            ->label('Produk')
            ->reactive()
            ->content(function (Get $get, Set $set) {
                $transactionId = $get('id');
                $customId = (int) $get('is_bundling') === 1 ? $get('bundling_id') : $get('product_id');

                $productName = '';
                if (!$customId) {
                    $detailTransaction = DetailTransaction::where('id', $transactionId)
                        ->select(['id', 'bundling_id', 'product_id'])
                        ->first();
                    if ($detailTransaction) {
                        if ($detailTransaction->bundling_id) {
                            $bundlingId = $detailTransaction->bundling_id;
                            $bundlingProducts = Bundling::find($bundlingId)?->products;
                            $productName = $bundlingProducts->pluck('name')->implode(', ');
                        } elseif ($detailTransaction->product_id) {
                            $product = Product::find($detailTransaction->product_id);
                            $productName = $product?->name ?? '-';
                        }
                    }
                } elseif ((int) $get('is_bundling') === 1) {
                    $bundling = Bundling::where('id', $customId)->first();
                    if ($bundling) {
                        $productName = $bundling->products->pluck('name')->implode(', ');
                    }
                } else {
                    $product = Product::find($customId);
                    $productName = $product?->name ?? '-';
                }
                return $productName;
            })
            ->columnStart(1);
    }

    public static function availableQuantityDisplay(): Placeholder
    {
        return Placeholder::make('available_quantity_display')
            ->label('Tersedia')
            ->reactive()
            ->content(function (Get $get, Set $set, $record) {
                $customId = (int) $get('is_bundling') === 1 ? $get('bundling_id') : $get('product_id');
                $startDate = Carbon::parse($get('../../start_date'));
                $endDate = Carbon::parse($get('../../end_date'));
                $transactionId = $get('id');

                if (!$customId) {
                    $detailTransaction = DetailTransaction::where('id', $transactionId)
                        ->select(['available_quantity'])
                        ->first();
                    $availableQuantity = $detailTransaction?->available_quantity ?? 0;
                } elseif ((int) $get('is_bundling') === 1) {
                    $bundling = Bundling::where('id', $customId)->first();
                    $availableQuantity = 0;
                    $availableQuantities = [];
                    foreach ($bundling->products as $product) {
                        $productName = $product->name;
                        $includedStatuses = ['rented', 'paid', 'pending'];

                        $rentedQuantity = DetailTransaction::where('product_id', $product->id)
                            ->whereNotIn('id', [$transactionId])
                            ->whereHas('transaction', function ($query) use ($startDate, $endDate, $includedStatuses) {
                                $query->whereIn('booking_status', $includedStatuses)
                                    ->where('start_date', '<=', $endDate)
                                    ->where('end_date', '>=', $startDate);
                            })
                            ->sum('quantity');

                        $availableQuantity += $product->quantity - $rentedQuantity;
                        $set('available_quantity', max($product->quantity - $rentedQuantity, 0));

                        $availableQuantities[] = new HtmlString("<br>{$product->name}: <strong>{$availableQuantity}</strong> unit");

                        if ($availableQuantity <= 0) {
                            $availableQuantities[] = new HtmlString("<br><span style='color:red'>{$product->name} tidak tersedia pada rentang tanggal {$startDate->format('Y-m-d')} hingga {$endDate->format('Y-m-d')}.</span>");
                        }
                    }

                    return new HtmlString(implode('', $availableQuantities));
                } else {
                    $product = Product::find($customId);
                    $productName = $product?->name;

                    if ($product) {
                        $includedStatuses = ['rented', 'paid', 'pending'];

                        $rentedQuantity = DetailTransaction::where('product_id', $customId)
                            ->whereHas('transaction', function ($query) use ($startDate, $endDate, $includedStatuses) {
                                $query->whereIn('booking_status', $includedStatuses)
                                    ->where('start_date', '<=', $endDate)
                                    ->where('end_date', '>=', $startDate);
                            })
                            ->sum('quantity');

                        $availableQuantity = $product->quantity - $rentedQuantity;

                        if ($availableQuantity <= 0) {
                            return new HtmlString("<span style='color:red'>Produk {$productName} tidak tersedia pada rentang tanggal {$startDate->format('Y-m-d')} hingga {$endDate->format('Y-m-d')}.</span><br>");
                        }
                        $set('available_quantity', $availableQuantity);

                        return new HtmlString("Produk {$productName}: <strong>{$availableQuantity} unit</strong>");
                    } else {
                        return 'Produk belum diisi';
                    }
                }
            })
            ->columnStart(2);
    }

    public static function price(): Placeholder
    {
        return Placeholder::make('price')
            ->label('Harga')
            ->content(function (Get $get, Set $set) {
                $transactionId = $get('id');
                $customId = (int) $get('is_bundling') === 1 ? $get('bundling_id') : $get('product_id');

                if (!$customId) {
                    $detailTransaction = DetailTransaction::where('id', $transactionId)
                        ->value('price');
                    if ($detailTransaction) {
                        return 'Rp ' . number_format($detailTransaction, 0, ',', '.');
                    }
                }

                if ((int) $get('is_bundling') === 1) {
                    $bundling = Bundling::where('id', $customId)
                        ->value('price') ?? 0;
                } else {
                    $product = Product::find($customId);
                    if ($product) {
                        $product = $product->price ?? 0;
                    } else {
                        $product = 0;
                    }
                }

                $price = (int) ($bundling ?? $product);
                $set('price', $price);

                return 'Rp ' . number_format($price, 0, ',', '.');
            })
            ->columnSpan(1);
    }

    public static function totalPricePlaceholder(): Placeholder
    {
        return Placeholder::make('total_price_placeholder')
            ->label('Total')
            ->reactive()
            ->content(function ($state, Get $get, Set $set) {
                $customId = (int) $get('is_bundling') === 1 ? $get('bundling_id') : $get('product_id');
                $transactionId = $get('id');

                if (!$customId) {
                    $detailTransaction = DetailTransaction::where('id', $transactionId)
                        ->select(['id', 'bundling_id', 'product_id', 'available_quantity', 'total_price'])
                        ->first();
                    if ($detailTransaction && $detailTransaction->bundling_id) {
                        $bundlingId = $detailTransaction->bundling_id;
                        $bundlingProducts = Bundling::find($bundlingId)?->products;
                    } elseif ($detailTransaction && $detailTransaction->product_id) {
                        $product = Product::find($detailTransaction->product_id);
                    }
                    $totalAmount = $detailTransaction?->total_price ?? 0;

                    return 'Rp ' . number_format($totalAmount, 0, ',', '.');
                }
                if ((int) $get('is_bundling') === 1) {
                    $bundling = Bundling::where('custom_id', $customId)->first();
                } else {
                    $product = Product::where('custom_id', $customId)->first();
                }

                $productPrice = 0;
                if ((int) $get('is_bundling') === 1) {
                    $bundlingProducts = Bundling::find($customId);

                    $productPrice = $bundlingProducts ? $bundlingProducts->price : 0;
                } else {
                    $product = Product::find($customId);

                    $productPrice = $product ? $product->price : 0;
                }

                $quantity = $get('quantity') ?? 0;

                $totalAmount = $quantity * $productPrice;

                $set('total_before_discount', $totalAmount);
                $set('total_price', $totalAmount);

                return Number::currency($totalAmount, 'IDR');
            })
            ->columnSpan(1);
    }
}
