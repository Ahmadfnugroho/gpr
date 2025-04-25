<?php

namespace App\Filament\Resources;

use App\Filament\Imports\BundlingImporter;
use App\Filament\Resources\BundlingResource\Pages;
use App\Models\Bundling;
use Carbon\Carbon;
use Filament\Tables\Actions\ImportAction;
use Filament\Forms;
use Filament\Forms\Components\Builder;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Contracts\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder as DatabaseEloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Rmsramos\Activitylog\Actions\ActivityLogTimelineTableAction;

class BundlingResource extends Resource
{
    protected static ?string $model = Bundling::class;



    protected static ?string $recordTitleAttribute = 'name';

    public static function getGlobalSearchResultTitle(Model $record): string |  Htmlable
    {
        return $record->name;
    }
    public static function getGloballySearchableAttributes(): array
    {
        return [
            'name',
            'products.name',
            'products.category.name',
            'products.brand.name',
            'products.subCategory.name',

        ]; // Hanya mencari berdasarkan nama bundling
    }

    public static function getGlobalSearchEloquentQuery(): DatabaseEloquentBuilder
    {
        // Optimize query by eagerly loading related models
        return parent::getGlobalSearchEloquentQuery()->with(['products', 'transactions', 'detailTransactions']);
    }


    // Detail tambahan untuk hasil pencarian
    public static function getGlobalSearchResultDetails(\Illuminate\Database\Eloquent\Model $record): array
    {
        // Format harga bundling
        // $formattedPrice = 'Rp' . number_format($record->price, 0, ',', '.');

        // Daftar produk dalam bundling beserta statusnya

        $today = Carbon::now();

        // Status transaksi yang termasuk dalam perhitungan
        $includedStatuses = ['rented', 'paid', 'pending'];

        // Hitung available quantity untuk setiap produk dalam bundling
        $productsWithStatusAndAvailability = $record->products->map(function ($product) use ($today, $includedStatuses) {
            // Hitung rented quantity untuk produk ini
            $rentedQuantity = $product->detailTransactions
                ->filter(function ($detailTransaction) use ($today, $includedStatuses) {
                    // Ambil start_date dan end_date dari transaksi
                    $startDate = Carbon::parse($detailTransaction->transaction->start_date);
                    $endDate = Carbon::parse($detailTransaction->transaction->end_date);

                    // Cek status transaksi dan rentang tanggal
                    return in_array($detailTransaction->transaction->booking_status, $includedStatuses) &&
                        $startDate <= $today &&
                        $endDate >= $today;
                })
                ->sum('quantity'); // Jumlahkan quantity

            // Hitung available quantity
            $availableQuantity = $product->quantity - $rentedQuantity;

            // Tentukan status berdasarkan available quantity
            $status = $availableQuantity > 0 ? $product->status : 'unavailable';

            // Format hasil
            return "{$product->name} ( {$status}, Tersedia: {$availableQuantity})";
        })->implode(', '); // Gabungkan semua produk menjadi satu string yang dipisahkan oleh koma


        return [
            'Products' => $productsWithStatusAndAvailability, // Daftar produk dengan status dan available quantity
        ];
    }
    // Eager-load relationships untuk optimasi query


    protected static ?string $navigationIcon = 'heroicon-o-inbox-stack';
    protected static ?string $navigationGroup = 'Product';

    protected static ?string $navigationLabel = 'Bundling';
    protected static ?int $navigationSort = 28;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nama Bundling')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('price')
                    ->label('Harga')
                    ->required()
                    ->maxLength(255)
                    ->helperText('Contoh: 100000')
                    ->numeric(),
                Forms\Components\Select::make('products')
                    ->label('Produk')
                    ->relationship('products', 'name')
                    ->multiple()
                    ->required()
                    ->searchable()
                    ->preload()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                Tables\Columns\TextColumn::make('name')
                    ->label('Bundling Name'),
                Tables\Columns\TextColumn::make('price')
                    ->formatStateUsing(fn($state) => 'Rp' . number_format($state, 0, ',', '.'))

                    ->label('Price'),
                Tables\Columns\TextColumn::make('products')
                    ->label('Products')
                    ->formatStateUsing(
                        fn($state, $record) =>
                        $record->products->pluck('name')->join(', ')
                    )
                    ->tooltip(fn($record) => $record->products->pluck('name')->join("\n")),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),

                ActivityLogTimelineTableAction::make('Activities')
                    ->timelineIcons([
                        'created' => 'heroicon-m-check-badge',
                        'updated' => 'heroicon-m-pencil-square',
                    ])
                    ->timelineIconColors([
                        'created' => 'info',
                        'updated' => 'warning',
                    ])
                    ->limit(10),
            ])

            ->headerActions([
                ImportAction::make()
                    ->importer(BundlingImporter::class)
                    ->label('Import Bundling Product'),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBundlings::route('/'),
            'create' => Pages\CreateBundling::route('/create'),
            'edit' => Pages\EditBundling::route('/{record}/edit'),
        ];
    }
}
