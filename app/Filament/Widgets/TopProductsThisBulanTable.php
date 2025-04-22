<?php

namespace App\Filament\Widgets;

use App\Models\DetailTransaction;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Illuminate\Database\Eloquent\Model;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Log;

class TopProductsThisBulanTable extends BaseWidget
{
    use HasWidgetShield;

    protected static ?string $heading = '10 Produk Terlaris Bulan Ini';
    protected static bool $isLazy = false;
    protected static ?int $sort = 5;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                DetailTransaction::query()
                    ->selectRaw('product_id, bundling_id, MIN(id) as id, COUNT(*) as count')
                    ->whereHas('transaction', function ($query) {
                        $query
                            ->whereMonth('created_at', now()->month)
                            ->whereYear('created_at', now()->year);
                    })
                    ->groupBy('product_id', 'bundling_id')
                    ->orderByDesc('count')
                    ->limit(10)
                    ->with(['product', 'bundling']) // Load related models
            )
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Nama Produk/Bundling')
                    ->formatStateUsing(function ($state, $record) {
                        // Cek apakah bundling_id ada
                        if ($record->bundling_id && $record->bundling) {
                            return $record->bundling->name; // Tampilkan nama bundling
                        }

                        // Cek apakah product_id ada
                        if ($record->product_id && $record->product) {
                            return $record->product->name; // Tampilkan nama produk
                        }

                        // Jika keduanya null
                        return 'Tidak Ditemukan';
                    })
                    ->sortable()
                    ->searchable(),


                Tables\Columns\TextColumn::make('count')
                    ->label('Jumlah')
                    ->sortable()
                    ->formatStateUsing(fn($state) => number_format($state)),
            ])
            ->defaultSort('count', 'desc');
    }

    /**
     * Generate a unique key for each record.
     *
     * @param Model $record
     * @return string
     */
    public function getTableRecordKey(Model $record): string
    {
        return uniqid();
    }
}
