<?php

namespace App\Filament\Resources;


use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Filament\Exports\ProductExporter;
use App\Filament\Imports\ProductImporter;
use App\Models\Category;
use App\Models\Brand;
use App\Models\SubCategory;

use App\Models\Product;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\ExportAction;
use Filament\Tables\Actions\ImportAction;

use Illuminate\Database\Eloquent\Builder;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Columns\ColumnGroup;
use Illuminate\Database\Eloquent\Model;
use Rmsramos\Activitylog\Actions\ActivityLogTimelineTableAction;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\FacadesLog;
use Illuminate\Support\HtmlString;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;
    protected static ?string $recordTitleAttribute = 'name';

    public static function getGlobalSearchResultTitle(Model $record): string |  Htmlable
    {
        return $record->name;
    }

    public static function getGloballySearchableAttributes(): array
    {
        return [
            'name', // Nama produk (prioritas utama)
            'items.serial_number', // Serial number produk
        ];
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()
            ->with(['category:id,name', 'brand:id,name', 'subCategory:id,name', 'items:id,product_id,serial_number'])
            ->where('status', '!=', 'deleted');
    }

    public static function modifyGlobalSearchQuery(Builder $query, string $search): void
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
            // All words must be present (in any order)
            foreach ($searchWords as $word) {
                $wordProximityConditions[] = "LOWER(name) LIKE ?";
                $wordProximityParams[] = '%' . $word . '%';
            }
            $allWordsCondition = implode(' AND ', $wordProximityConditions);
            
            // Sequential words (exact phrase)
            $sequentialMatch = '%' . implode('%', $searchWords) . '%';
        }
        
        $orderBySQL = "CASE ";
        $orderByParams = [];
        
        // Priority 1: Exact match
        $orderBySQL .= "WHEN LOWER(name) LIKE ? THEN 1 ";
        $orderByParams[] = $exactMatch;
        
        // Priority 2: Starts with search term
        $orderBySQL .= "WHEN LOWER(name) LIKE ? THEN 2 ";
        $orderByParams[] = $startsWithMatch;
        
        if (count($searchWords) > 1) {
            // Priority 3: Sequential match (phrase match)
            $orderBySQL .= "WHEN LOWER(name) LIKE ? THEN 3 ";
            $orderByParams[] = $sequentialMatch;
            
            // Priority 4: All words present
            $orderBySQL .= "WHEN {$allWordsCondition} THEN 4 ";
            $orderByParams = array_merge($orderByParams, $wordProximityParams);
        }
        
        // Priority 5 (or 3 for single word): Contains match
        $orderBySQL .= "WHEN LOWER(name) LIKE ? THEN " . (count($searchWords) > 1 ? "5" : "3") . " ";
        $orderByParams[] = $containsMatch;
        
        $orderBySQL .= "ELSE 10 END, CHAR_LENGTH(name)";
        
        $query->orderByRaw($orderBySQL, $orderByParams);
    }
    public static function getGlobalSearchResultDetails(\Illuminate\Database\Eloquent\Model $record): array
    {
        $today = Carbon::now();
        $available = $record->items()->where('is_available', true)->count();
        $totalItems = $record->items()->count();
        
        return [
            'Category' => $record->category?->name ?? '-',
            'Brand' => $record->brand?->name ?? '-',
            'Status' => $record->status === 'available' ? '✅ Available' : '❌ Unavailable',
            'Stock' => "{$available}/{$totalItems} units",
        ];
    }

    protected static ?string $navigationIcon = 'heroicon-o-cube-transparent';

    protected static ?string $navigationGroup = 'Product';

    protected static ?string $navigationLabel = 'Products';

    // protected static bool $shouldRegisterNavigation = false;

    protected static ?int $navigationSort = 21;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nama Produk')
                    ->required()
                    ->maxLength(255),


                Forms\Components\TextInput::make('price')
                    ->label('Harga Produk')
                    ->required()
                    ->numeric()
                    ->prefix('Rp'),
                Forms\Components\FileUpload::make('thumbnail')
                    ->label('Foto Produk')
                    ->image()
                    ->nullable(),
                Select::make('status')
                    ->label('Status')
                    ->options([
                        'available' => 'Tersedia',
                        'unavailable' => 'Tidak Tersedia',
                    ])
                    ->formatStateUsing(function ($record) {
                        if (!$record) return 'available'; // Default saat create
                        return $record->items()->where('is_available', true)->count() > 0
                            ? 'available'
                            : 'unavailable';
                    })
                    ->required()
                    ->reactive(),
                Forms\Components\Select::make('category_id')
                    ->relationship('category', 'name')
                    ->label('Kategori')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->nullable(),
                Forms\Components\Select::make('brand_id')
                    ->relationship('brand', 'name')
                    ->searchable()
                    ->preload()
                    ->nullable(),
                Forms\Components\Select::make('sub_category_id')
                    ->label('Sub Kategori')
                    ->relationship('subCategory', 'name')
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->nullable(),
                Forms\Components\Toggle::make('premiere')
                    ->label('Brand Premiere')
                    ->default(false),

                Forms\Components\Repeater::make('items')
                    ->label('Nomor Seri Produk')
                    ->relationship('items')
                    ->schema([
                        Forms\Components\TextInput::make('serial_number')
                            ->label('Nomor Seri')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Toggle::make('is_available')
                            ->label('Tersedia')
                            ->default(true),
                    ])
                    ->columns(2)
                    ->minItems(0)
                    ->reactive()
                    ->addActionLabel('Tambah Nomor Seri'),

                Forms\Components\Placeholder::make('available_serial_numbers')
                    ->label('Serial Number Tersedia')
                    ->content(function ($record) {
                        if (! $record) return '-';

                        $tersedia = $record->items->where('is_available', true)->pluck('serial_number')->toArray();
                        $tidakTersedia = $record->items->where('is_available', false)->pluck('serial_number')->toArray();

                        // Format tersedia dengan batasan 5 item
                        $tersediaHtml = '';
                        if (count($tersedia) > 0) {
                            if (count($tersedia) <= 5) {
                                $tersediaHtml = '<span style="color:green">Tersedia: ' . implode(', ', array_map('e', $tersedia)) . '</span>';
                            } else {
                                $first5 = array_slice($tersedia, 0, 5);
                                $remaining = count($tersedia) - 5;
                                $tersediaHtml = '<span style="color:green">Tersedia: ' . implode(', ', array_map('e', $first5)) . ' <span style="color:#6b7280; font-style:italic;">dan ' . $remaining . ' lainnya</span></span>';
                            }
                        } else {
                            $tersediaHtml = '<span style="color:red">Tidak ada serial number tersedia</span>';
                        }

                        // Format tidak tersedia dengan batasan 5 item
                        $tidakTersediaHtml = '';
                        if (count($tidakTersedia) > 0) {
                            if (count($tidakTersedia) <= 5) {
                                $tidakTersediaHtml = '<br><span style="color:gray">Tidak Tersedia: ' . implode(', ', array_map('e', $tidakTersedia)) . '</span>';
                            } else {
                                $first5 = array_slice($tidakTersedia, 0, 5);
                                $remaining = count($tidakTersedia) - 5;
                                $tidakTersediaHtml = '<br><span style="color:gray">Tidak Tersedia: ' . implode(', ', array_map('e', $first5)) . ' <span style="color:#6b7280; font-style:italic;">dan ' . $remaining . ' lainnya</span></span>';
                            }
                        }

                        return new HtmlString($tersediaHtml . $tidakTersediaHtml);
                    })
                    ->visible(fn($record) => $record !== null)
                    ->columnSpanFull()
                    ->extraAttributes(['class' => 'prose'])

            ]);
    }
    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([
                // Tombol ekspor produk
                ExportAction::make()
                    ->exporter(ProductExporter::class)
                    ->formats([
                        ExportFormat::Xlsx,
                    ]),

                // Tombol Import Produk
                ImportAction::make()
                    ->importer(ProductImporter::class)
                    ->label('Import Product'),

            ])

            ->columns([
                ColumnGroup::make(
                    '',
                    [
                        Tables\Columns\TextColumn::make('name')
                            ->searchable()
                            ->wrap()
                            ->alignCenter()
                            ->sortable(),
                        Tables\Columns\TextColumn::make('items_count')
                            ->label('Qty')
                            ->counts('items')
                            ->alignCenter()
                            ->sortable(),


                        Tables\Columns\TextColumn::make('items.serial_number')
                            ->label('Nomor Seri')
                            ->formatStateUsing(function($record) {
                                $serialNumbers = $record->items->pluck('serial_number')->toArray();
                                
                                if (count($serialNumbers) === 0) {
                                    return '-';
                                }
                                
                                if (count($serialNumbers) <= 5) {
                                    return implode(', ', $serialNumbers);
                                }
                                
                                $first5 = array_slice($serialNumbers, 0, 5);
                                $remaining = count($serialNumbers) - 5;
                                
                                return implode(', ', $first5) . " <span style='color: #6b7280; font-style: italic;'>dan {$remaining} lainnya</span>";
                            })
                            ->html()
                            ->searchable()
                            ->wrap()
                            ->sortable(),

                        Tables\Columns\TextColumn::make('price')
                            ->formatStateUsing(fn($state) => 'Rp' . number_format($state, 0, ',', '.'))
                            ->searchable()
                            ->sortable(),
                    ]
                ),

                ColumnGroup::make('Status', [
                    Tables\Columns\SelectColumn::make('status')
                        ->options([
                            'available' => 'Available',
                            'unavailable' => 'Unavailable',
                            'maintenance' => 'Maintenance',
                        ])
                        ->sortable(),

                    Tables\Columns\ToggleColumn::make('premiere')
                        ->label('Featured')
                        ->sortable()
                        ->width('1%'),
                ])
                    ->alignment(Alignment::Center)
                    ->wrapHeader()

            ])
            ->modifyQueryUsing(fn(Builder $query) => $query->withCount('items'))



            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'available' => 'available',
                        'unavailable' => 'unavailable',
                    ])
                    ->label('Status')
                    ->multiple()
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Kategori')
                    ->options(Category::all()->pluck('name', 'id')->toArray())
                    ->searchable()
                    ->multiple()
                    ->preload(),
                Tables\Filters\SelectFilter::make('brand_id')
                    ->label('Brand')
                    ->options(Brand::all()->pluck('name', 'id')->toArray())
                    ->searchable()
                    ->multiple()
                    ->preload(),
                Tables\Filters\SelectFilter::make('sub_category_id')
                    ->label('Sub Kategori')
                    ->options(SubCategory::all()->pluck('name', 'id')->toArray())
                    ->searchable()
                    ->multiple()
                    ->preload(),
            ])

            ->actions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\ViewAction::make(),
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
                        ]),
                ])
                    ->label('Lihat/Ubah Produk')
                    ->icon('heroicon-o-eye'),
            ])

            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\Action::make('available')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->label('available')
                        ->requiresConfirmation()
                        ->action(function (Product $record) {
                            $record->update(['status' => 'available']);
                            Notification::make()
                                ->success()
                                ->title('Berhasil Mengubah Status Produk')
                                ->send();
                        }),
                    Tables\Actions\Action::make('unavailable')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->label('unavailable')
                        ->requiresConfirmation()
                        ->action(function (Product $record) {
                            $record->update(['status' => 'unavailable']);
                            Notification::make()
                                ->success()
                                ->title('Berhasil Mengubah Status Product')
                                ->send();
                        })
                ])
                    ->label('Ubah Status Produk'),

                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('enable_featured')
                        ->icon('heroicon-o-star')
                        ->color('warning')
                        ->label('Aktifkan Featured')
                        ->requiresConfirmation()
                        ->modalHeading('Aktifkan Featured')
                        ->modalDescription('Apakah Anda yakin ingin mengaktifkan featured untuk produk yang dipilih?')
                        ->modalSubmitActionLabel('Ya, Aktifkan')
                        ->action(function ($records) {
                            $count = $records->count();
                            $records->each(function ($record) {
                                $record->update(['premiere' => true]);
                            });
                            Notification::make()
                                ->success()
                                ->title('Berhasil Mengaktifkan Featured')
                                ->body("{$count} produk berhasil diaktifkan sebagai featured.")
                                ->send();
                        }),
                    Tables\Actions\BulkAction::make('disable_featured')
                        ->icon('heroicon-o-star')
                        ->color('gray')
                        ->label('Nonaktifkan Featured')
                        ->requiresConfirmation()
                        ->modalHeading('Nonaktifkan Featured')
                        ->modalDescription('Apakah Anda yakin ingin menonaktifkan featured untuk produk yang dipilih?')
                        ->modalSubmitActionLabel('Ya, Nonaktifkan')
                        ->action(function ($records) {
                            $count = $records->count();
                            $records->each(function ($record) {
                                $record->update(['premiere' => false]);
                            });
                            Notification::make()
                                ->success()
                                ->title('Berhasil Menonaktifkan Featured')
                                ->body("{$count} produk berhasil dinonaktifkan dari featured.")
                                ->send();
                        })
                ])
                    ->label('Ubah Featured Produk'),

                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ProductPhotoRelationManager::class,
            RelationManagers\ProductSpecificationRelationManager::class,
            RelationManagers\RentalIncludeRelationManager::class
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
