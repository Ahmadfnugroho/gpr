<?php

namespace App\Filament\Resources\TransactionResource\FormSections\ProductList;

use Filament\Forms\Components\Select;
use App\Models\ProductItem;
use Carbon\Carbon;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Support\Facades\Log;

class SerialNumberSelect
{
    public static function make(): Select
    {
        return Select::make('serial_numbers')
            ->label('Pilih Nomor Seri')
            ->multiple()
            ->options(function (Get $get) {
                $productId = $get('product_id');
                if (!$productId) return [];

                $startDate = Carbon::parse($get('../../start_date'));
                $endDate = Carbon::parse($get('../../end_date'));

                $productItems = ProductItem::where('product_id', $productId)
                    ->where('is_available', true)
                    ->get();

                Log::info('ProductItems count for product id ' . $productId . ': ' . $productItems->count());

                // Diagnostic log: product item ids and their productTransactions count
                foreach ($productItems as $item) {
                    Log::info('ProductItem id ' . $item->id . ' has ' . $item->productTransactions()->count() . ' productTransactions');
                }

                // Log comprehensive transaction data related to the product
                $transactionsData = [];
                foreach ($productItems as $item) {
                    $productTransactions = $item->productTransactions()
                        ->with('transaction') // pastikan relasi transaction dimuat
                        ->get();

                    foreach ($productTransactions as $pt) {
                        $bookingStatus = $pt->transaction->booking_status ?? null;
                        Log::info('Booking Status for ProductTransaction id ' . $pt->id . ': ' . $bookingStatus);

                        $transactionsData[] = [
                            'transaction' => $pt->transaction ? $pt->transaction->toArray() : null,
                            'detail_transaction' => $pt->transaction ? $pt->transaction->detailTransactions->map(fn($dt) => $dt->toArray()) : null,
                            'product' => $pt->product ? $pt->product->toArray() : null,
                            'product_item' => $pt->productItem ? $pt->productItem->toArray() : null,
                        ];
                    }
                }
                Log::info('Comprehensive transaction data related to product id ' . $productId, $transactionsData);

                $availableProductItems = $productItems->filter(function ($item) use ($startDate, $endDate) {
                    $overlappingBooking = $item->productTransactions()
                        ->whereHas('transaction', function ($query) use ($startDate, $endDate) {
                            $query->whereIn('booking_status', ['rented', 'paid', 'pending'])
                                ->where('start_date', '<=', $endDate)
                                ->where('end_date', '>=', $startDate);
                        })
                        ->first(); // using `first()` for more efficient checking

                    Log::info("Checking ProductItem ID: {$item->id}", [
                        'start_date' => $startDate->toDateTimeString(),
                        'end_date' => $endDate->toDateTimeString(),
                        'overlapping_booking' => $overlappingBooking ? 'Yes' : 'No',
                    ]);

                    return !$overlappingBooking;
                });

                return $availableProductItems->mapWithKeys(function ($item) {
                    return [$item->serial_number => "{$item->product->name} - {$item->serial_number}"];
                });
            })
            ->reactive()
            ->afterStateUpdated(function ($state, Set $set) {
                $set('quantity', count($state));
            });
    }
}
