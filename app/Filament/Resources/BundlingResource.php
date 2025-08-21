<?php

namespace App\Filament\Resources;

use App\Filament\Exports\BundlingExporter;
use App\Filament\Imports\BundlingImporter;
use App\Filament\Resources\BundlingResource\Pages;
use App\Models\Bundling;
use App\Models\Product;
use Carbon\Carbon;
use Filament\Tables\Actions\ExportAction;
use Filament\Tables\Actions\ImportAction;
use Filament\Actions\Exports\Enums\ExportFormat;

use Filament\Forms;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
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
        $today = Carbon::now();
        $startDate = $today;
        $endDate = $today->copy()->addDay();
        $available = $record->getAvailableQuantityForPeriod($startDate, $endDate, 1);
        $productsWithStatusAndAvailability = $record->products->map(function ($product) use ($startDate, $endDate) {
            $available = $product->getAvailableQuantityForPeriod($startDate, $endDate);
            $serials = implode(', ', $product->getAvailableSerialNumbersForPeriod($startDate, $endDate));
            $status = $available > 0 ? 'available' : 'unavailable';
            return "{$product->name} ({$status}, Tersedia: {$available}, Serials: {$serials})";
        })->implode(', ');
        return [
            'Products' => $productsWithStatusAndAvailability,
            'Bundling Available' => $available,
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
                TextInput::make('name')
                    ->label('Nama Bundling')
                    ->required()
                    ->maxLength(255),

                TextInput::make('price')
                    ->label('Harga')
                    ->numeric()
                    ->prefix('Rp'),

                Repeater::make('bundlingProducts')
                    ->relationship('bundlingProducts') // relasi hasMany ke model pivot
                    ->label('Produk dalam Bundling')
                    ->schema([
                        Select::make('product_id')
                            ->label('Pilih Produk')
                            ->options(Product::pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required(),

                        TextInput::make('quantity')
                            ->label('Jumlah')
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->required(),
                    ])
                    ->columns(2)
                    ->addActionLabel('Tambah Produk')
                    ->collapsible()
                    ->deletable()
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
                ExportAction::make()
                    ->exporter(BundlingExporter::class)
                    ->formats([
                        ExportFormat::Xlsx,
                    ]),
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
