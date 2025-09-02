<?php

namespace App\Filament\Resources\TransactionResource\TableColumns;

use App\Filament\Resources\TransactionResource\FormSections\ProductList\Number;
use App\Models\Bundling;
use App\Models\Product;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextColumn\TextColumnSize;
use Filament\Tables\Columns\TextInputColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\HtmlString;

class TransactionProductColumn
{

    public static function get(): array
    {

        return [

            TextColumn::make('id')
                ->size(TextColumnSize::ExtraSmall)


                ->label('No')
                ->wrap()

                ->searchable()
                ->sortable(),
            TextColumn::make('customer.name')
                ->label('Customer')
                ->wrap()
                ->size(TextColumnSize::ExtraSmall)


                ->searchable()
                ->sortable(),
            TextColumn::make('customer.customerPhoneNumbers')
                ->label('Phone')
                ->formatStateUsing(fn($record) => optional($record->customer->customerPhoneNumbers->first())->phone_number ?? '-')

                ->wrap()
                ->size(TextColumnSize::ExtraSmall)


                ->searchable(),


            TextColumn::make('DetailTransactions.id')
                ->label('Produk')
                ->wrap()
                ->size(TextColumnSize::ExtraSmall)


                ->formatStateUsing(function ($record) {
                    if (!$record->DetailTransactions || $record->DetailTransactions->isEmpty()) {
                        return new HtmlString('-');
                    }

                    $detailTransactions = $record->DetailTransactions;

                    $productNames = []; // Untuk menyimpan nama produk
                    $bundlingNames = []; // Untuk menyimpan nama bundling dan produknya

                    foreach ($detailTransactions as $detailTransaction) {
                        // Jika ada product_id, ambil nama produk
                        if ($detailTransaction->product_id) {
                            $product = Product::find($detailTransaction->product_id);
                            if ($product) {
                                $productNames[] = e($product->name); // Escape nama produk untuk keamanan
                            }
                        }

                        // Jika ada bundling_id, ambil nama bundling dan produknya
                        if ($detailTransaction->bundling_id) {
                            $bundling = Bundling::with('products')->find($detailTransaction->bundling_id);
                            if ($bundling) {
                                $bundlingProducts = $bundling->products->pluck('name')->map(function ($name) {
                                    return e($name); // Escape nama produk
                                })->implode('<br>'); // Gabungkan nama produk dengan <br>
                                $bundlingNames[] = "({$bundlingProducts})"; // Tambahkan ke array bundling
                            }
                        }
                    }

                    // Gabungkan semua nama produk dan bundling
                    $allNames = array_merge($productNames, $bundlingNames);

                    // Jika ada nama, tambahkan nomor urut di setiap item
                    if (!empty($allNames)) {
                        $result = '<ol>'; // Mulai dengan tag <ol> untuk ordered list
                        foreach ($allNames as $name) {
                            $result .= "<li>{$name}</li>"; // Tambahkan setiap nama dalam tag <li>
                        }
                        $result .= '</ol>'; // Tutup tag <ol>
                    } else {
                        $result = '-'; // Jika tidak ada nama, kembalikan '-'
                    }


                    // Kembalikan sebagai HtmlString
                    return new HtmlString($result);
                }),

            TextColumn::make('DetailTransactions.serial_numbers')
                ->label('Nomor Seri')
                ->formatStateUsing(function ($record) {
                    if (!$record->DetailTransactions || $record->DetailTransactions->isEmpty()) {
                        return '-';
                    }

                    $serials = [];

                    foreach ($record->DetailTransactions as $detail) {
                        if (is_array($detail->serial_numbers)) {
                            $serials = array_merge($serials, $detail->serial_numbers);
                        }
                    }

                    return implode(', ', array_filter($serials));
                })
                ->wrap()
                ->size(TextColumnSize::ExtraSmall)
                ->toggleable(),
            TextColumn::make('booking_transaction_id')
                ->label('Trx Id')
                ->wrap()
                ->size(TextColumnSize::ExtraSmall)

                ->sortable()
                ->searchable(),
            TextColumn::make('start_date')
                ->label('Start')
                ->wrap()
                ->size(TextColumnSize::ExtraSmall)
                ->formatStateUsing(fn(string $state): string => Carbon::parse($state)->locale('id_ID')->isoFormat('DD MMM YYYY hh:mm'))
                ->sortable()
                ->searchable(),
            TextColumn::make('end_date')
                ->label('End')
                ->wrap()
                ->size(TextColumnSize::ExtraSmall)
                ->formatStateUsing(fn(string $state): string => Carbon::parse($state)->locale('id_ID')->isoFormat('DD MMM YYYY hh:mm'))
                ->sortable()
                ->searchable(),
            TextColumn::make('grand_total')
                ->label('Total')
                ->wrap()
                ->size(TextColumnSize::ExtraSmall)
                ->formatStateUsing(fn(string $state): string => Number::currency((int) $state / 1000, 'IDR') . 'K')
                ->sortable()
                ->searchable(),
            TextInputColumn::make('down_payment')
                ->label('DP')
                ->default(
                    function (Get $get, Set $set): int {
                        $downPayment = $get('grand_total') * 0.5;
                        $set('down_payment', $downPayment);

                        return $downPayment;
                    }
                )
                ->sortable(),
            TextColumn::make('remaining_payment')
                ->label('Sisa')
                ->wrap()

                ->formatStateUsing(fn(string $state): HtmlString => new HtmlString($state == '0' ? '<strong style="color: green">LUNAS</strong>' : Number::currency((int) $state / 1000, 'IDR') . 'K'))

                ->size(TextColumnSize::ExtraSmall)
                ->sortable(),

            TextColumn::make('booking_status')
                ->label('')
                ->wrap()
                ->size(TextColumnSize::ExtraSmall)

                ->icon(fn(string $state): string => match ($state) {
                    'booking' => 'heroicon-o-clock',
                    'cancel' => 'heroicon-o-x-circle',
                    'on_rented' => 'heroicon-o-shopping-bag',
                    'done' => 'heroicon-o-check',
                    'paid' => 'heroicon-o-banknotes',
                })
                ->color(fn(string $state): string => match ($state) {
                    'booking' => 'warning',
                    'cancel' => 'danger',
                    'on_rented' => 'info',
                    'done' => 'success',
                    'paid' => 'success',
                }),


        ];
    }
}
