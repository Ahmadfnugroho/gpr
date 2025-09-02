<?php

namespace App\Filament\Widgets;

use App\Models\DetailTransaction;
use App\Models\Product;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn\TextColumnSize;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\HtmlString;

class RentedProductsToday extends BaseWidget
{
    use HasWidgetShield;

    protected static ?string $heading = 'Produk yang Disewa Hari Ini';
    protected static bool $isLazy = false;
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';


    public function table(Table $table): Table
    {
        return $table
            ->query(
                DetailTransaction::with(['product', 'bundling', 'transaction.customer'])
                    ->whereHas('transaction', function ($query) {
                        $query->whereDate('start_date', today())
                            ->whereIn('booking_status', ['booking', 'paid', 'on_']);
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

                Tables\Columns\TextColumn::make('transaction.customer.name')
                    ->label('Nama Customer')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('transaction.start_date')
                    ->label('Tanggal Mulai')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('transaction.start_date', 'asc'); // Urutkan berdasarkan tanggal mulai
    }
}
