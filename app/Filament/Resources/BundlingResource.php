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
            ->with(['products:id,name', 'bundlingProducts:id,bundling_id,product_id,quantity'])
            ->whereHas('products'); // Hanya bundling yang memiliki produk
    }

    public static function modifyGlobalSearchQuery(DatabaseEloquentBuilder $query, string $search): void
    {
        // Clean and normalize search term
        $searchTerm = trim(strtolower($search));
        
        // If empty search, do nothing
        if (empty($searchTerm)) {
            return;
        }
        
        // Split search into words for better matching
        $searchWords = array_filter(explode(' ', $searchTerm));
        
        // Create different scoring based on match types
        $exactMatch = $searchTerm;
        $startsWithMatch = $searchTerm . '%';
        $containsMatch = '%' . $searchTerm . '%';
        
        // For multi-word search, create patterns for word proximity
        $wordProximityConditions = [];
        $wordProximityParams = [];
        
        if (count($searchWords) > 1) {
            // All words must be present in bundling name (in any order)
            foreach ($searchWords as $word) {
                $wordProximityConditions[] = "LOWER(bundlings.name) LIKE ?";
                $wordProximityParams[] = '%' . $word . '%';
            }
            $allWordsCondition = implode(' AND ', $wordProximityConditions);
            
            // Sequential words (exact phrase)
            $sequentialMatch = '%' . implode('%', $searchWords) . '%';
            
            // All words in product names
            $productWordConditions = [];
            foreach ($searchWords as $word) {
                $productWordConditions[] = "LOWER(p.name) LIKE ?";
            }
            $allProductWordsCondition = implode(' AND ', $productWordConditions);
        }
        
        $orderBySQL = "CASE ";
        $orderByParams = [];
        
        // Priority 1: Exact match bundling name
        $orderBySQL .= "WHEN LOWER(bundlings.name) LIKE ? THEN 1 ";
        $orderByParams[] = $exactMatch;
        
        // Priority 2: Bundling name starts with search term
        $orderBySQL .= "WHEN LOWER(bundlings.name) LIKE ? THEN 2 ";
        $orderByParams[] = $startsWithMatch;
        
        if (count($searchWords) > 1) {
            // Priority 3: Sequential match in bundling name (phrase match)
            $orderBySQL .= "WHEN LOWER(bundlings.name) LIKE ? THEN 3 ";
            $orderByParams[] = $sequentialMatch;
            
            // Priority 4: All words present in bundling name
            $orderBySQL .= "WHEN {$allWordsCondition} THEN 4 ";
            $orderByParams = array_merge($orderByParams, $wordProximityParams);
            
            // Priority 5: All words present in any product name
            $orderBySQL .= "WHEN EXISTS(
                SELECT 1 FROM bundling_products bp 
                JOIN products p ON bp.product_id = p.id 
                WHERE bp.bundling_id = bundlings.id 
                AND {$allProductWordsCondition}
            ) THEN 5 ";
            $orderByParams = array_merge($orderByParams, array_map(fn($word) => '%' . $word . '%', $searchWords));
        }
        
        // Priority 6 (or 3 for single word): Contains match in bundling name
        $orderBySQL .= "WHEN LOWER(bundlings.name) LIKE ? THEN " . (count($searchWords) > 1 ? "6" : "3") . " ";
        $orderByParams[] = $containsMatch;
        
        // Priority 7 (or 4 for single word): Contains match in product name
        $orderBySQL .= "WHEN EXISTS(
            SELECT 1 FROM bundling_products bp 
            JOIN products p ON bp.product_id = p.id 
            WHERE bp.bundling_id = bundlings.id 
            AND LOWER(p.name) LIKE ?
        ) THEN " . (count($searchWords) > 1 ? "7" : "4") . " ";
        $orderByParams[] = $containsMatch;
        
        $orderBySQL .= "ELSE 10 END, CHAR_LENGTH(bundlings.name)";
        
        $query->orderByRaw($orderBySQL, $orderByParams);
    }


    // Detail tambahan untuk hasil pencarian
    public static function getGlobalSearchResultDetails(\Illuminate\Database\Eloquent\Model $record): array
    {
        $productNames = $record->products->pluck('name')->take(3)->toArray();
        $totalProducts = $record->products->count();
        $productDisplay = implode(', ', $productNames);
        
        if ($totalProducts > 3) {
            $productDisplay .= " +" . ($totalProducts - 3) . " more";
        }
        
        return [
            'Products' => $productDisplay ?: 'No products',
            'Total Items' => $totalProducts . ' products',
            'Price' => 'Rp ' . number_format($record->price, 0, ',', '.'),
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
