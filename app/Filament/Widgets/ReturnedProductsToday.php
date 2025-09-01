<?php

namespace App\Filament\Widgets;

use App\Models\DetailTransaction;
use App\Models\Product;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Carbon\Carbon;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn\TextColumnSize;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;

class ReturnedProductsToday extends BaseWidget

{
    use HasWidgetShield;

    protected static ?string $heading = 'Produk yang Dikembalikan Hari Ini';
    protected static bool $isLazy = false;
    protected static ?int $sort = 1;
    protected int | string | array $columnSpan = 'full';





    public function table(Table $table): Table
    {
        return $table
            ->query(
                DetailTransaction::with(['product', 'bundling', 'transaction.user'])
                    ->whereHas('transaction', function ($query) {
                        $query->whereDate('end_date', today())
                            ->whereIn('booking_status', ['booking', 'paid', 'on_rented']);
                    })
                    ->select(['id', 'product_id', 'bundling_id', 'transaction_id']) // Tambahkan kolom 'id' dan relasi terkait

            )
            ->columns([

                Tables\Columns\TextColumn::make('bundling')
                    ->label('')
                    ->sortable()
                    ->searchable()
                    ->wrap()
                    ->size(TextColumnSize::ExtraSmall)
                    ->formatStateUsing(function (DetailTransaction $record) {
                        // Retrieve product and bundling IDs
                        $productId = $record->product_id;
                        $bundlingId = $record->bundling_id;

                        // Get product name if productId exists
                        $productName = $productId ? Product::find($productId)?->name : '';

                        // Get bundling products if bundlingId exists
                        if ($bundlingId) {
                            $bundling = \App\Models\Bundling::with('products')->find($bundlingId);
                            if ($bundling) {
                                return 'bundling';
                            }
                        }

                        // Return the result as a string
                        return $productName ?: '';
                    }),


                Tables\Columns\TextColumn::make('id')
                    ->label('Produk')
                    ->wrap()
                    ->size(TextColumnSize::ExtraSmall)
                    ->formatStateUsing(function (DetailTransaction $record) {
                        // Retrieve product and bundling IDs
                        $productId = $record->product_id;
                        $bundlingId = $record->bundling_id;

                        // Get product name if productId exists
                        $productName = $productId
                            ? Product::where('id', $productId)->value('name')
                            : '';

                        // Get bundling products if bundlingId exists
                        if ($bundlingId) {
                            $bundling = \App\Models\Bundling::with('products')->find($bundlingId);
                            if ($bundling) {
                                $bundlingProducts = $bundling->products->pluck('name')->map(function ($name) {
                                    return e($name); // Escape HTML untuk keamanan
                                })->implode('<br>');
                                $productName .= "<br>({$bundlingProducts})";
                            }
                        }

                        // Return the result as an HtmlString
                        return new HtmlString($productName ?? '-');
                    }),


                Tables\Columns\TextColumn::make('transaction.user.name')
                    ->label('Nama User')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('transaction.end_date')
                    ->label('Tanggal Berakhir')
                    ->dateTime()
                    ->formatStateUsing(fn(string $state): string => Carbon::parse($state)->format('d M Y H:i'))

                    ->sortable(),
            ])
            ->defaultSort('transaction.end_date', 'asc'); // Urutkan berdasarkan tanggal berakhir
    }
}
