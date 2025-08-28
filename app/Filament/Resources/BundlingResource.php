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
            'name', // Nama bundling (prioritas utama)
            'products.name', // Nama produk dalam bundling
        ];
    }

    public static function getGlobalSearchEloquentQuery(): DatabaseEloquentBuilder
    {
        // Optimize query dengan eager loading minimal
        return parent::getGlobalSearchEloquentQuery()
            ->with([
                'products:id,name',
                'bundlingProducts:id,bundling_id,product_id,quantity',
                'detailTransactions.transaction:id,start_date,end_date,booking_status'
            ]);
    }

    public static function modifyGlobalSearchQuery(DatabaseEloquentBuilder $query, string $search): void
    {
        // Clean and normalize search term
        $searchTerm = trim(strtolower($search));
        
        // If empty search, do nothing
        if (empty($searchTerm)) {
            return;
        }
        
        // Override default search behavior to search for exact phrase only
        $query->where(function ($query) use ($searchTerm) {
            $query->whereRaw('LOWER(bundlings.name) LIKE ?', ['%' . $searchTerm . '%'])
                ->orWhereHas('products', function ($productQuery) use ($searchTerm) {
                    $productQuery->whereRaw('LOWER(products.name) LIKE ?', ['%' . $searchTerm . '%']);
                });
        });
    }


    // Detail tambahan untuk hasil pencarian
    public static function getGlobalSearchResultDetails(\Illuminate\Database\Eloquent\Model $record): array
    {
        $today = Carbon::now();
        
        // Get latest transaction dates
        $latestTransaction = $record->detailTransactions()
            ->with('transaction')
            ->whereHas('transaction', function ($query) {
                $query->where('booking_status', '!=', 'cancelled');
            })
            ->latest('created_at')
            ->first();

        $transactionInfo = '-';
        if ($latestTransaction && $latestTransaction->transaction) {
            $startDate = $latestTransaction->transaction->start_date ? $latestTransaction->transaction->start_date->format('d M Y') : '-';
            $endDate = $latestTransaction->transaction->end_date ? $latestTransaction->transaction->end_date->format('d M Y') : '-';
            $transactionInfo = "{$startDate} - {$endDate}";
        }

        // Check availability based on current active transactions
        $hasActiveTransaction = $record->detailTransactions()
            ->whereHas('transaction', function ($query) use ($today) {
                $query->where('booking_status', '!=', 'cancelled')
                    ->where('start_date', '<=', $today)
                    ->where('end_date', '>=', $today);
            })
            ->exists();

        // If has active transaction today, then not available, otherwise available
        $isAvailable = !$hasActiveTransaction;
        $availabilityStatus = $isAvailable ? '🟢 Tersedia' : '🔴 Tidak Tersedia';

        return [
            'Latest Rental' => $transactionInfo,
            'Available' => $availabilityStatus,
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
            ->defaultPaginationPageOption(50)
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
