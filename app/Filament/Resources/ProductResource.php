<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BaseOptimizedResource;
use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Category;
use App\Models\Brand;
use App\Models\SubCategory;
use App\Models\Product;
use App\Services\ProductImportExportService;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\HeaderActions;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use App\Services\CustomNotificationService;
use App\Services\ResourceCacheService;
use App\Repositories\ProductRepository;
use App\Filament\Imports\ProductImporter;
use App\Filament\Exports\ProductExporter;
use Filament\Forms\Components\Checkbox;
use Filament\Tables\Actions\ImportAction;
use Filament\Tables\Actions\ExportAction;
use Illuminate\Database\Eloquent\Builder;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Forms\Components\Select;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Columns\ColumnGroup;
use Illuminate\Database\Eloquent\Model;
use Rmsramos\Activitylog\Actions\ActivityLogTimelineTableAction;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;

class ProductResource extends BaseOptimizedResource
{
    protected static ?string $model = Product::class;
    protected static ?string $recordTitleAttribute = 'name';

    /**
     * Repository instance for optimized data access
     */
    protected static ?ProductRepository $repository = null;

    /**
     * Get repository instance
     */
    protected static function getRepository(): ProductRepository
    {
        if (static::$repository === null) {
            static::$repository = new ProductRepository(new Product());
        }

        return static::$repository;
    }

    /**
     * Get columns to select for optimized queries
     */
    protected static function getSelectColumns(): array
    {
        return [
            'products.id',
            'products.name',
            'products.status',
            'products.premiere',
            'products.category_id',
            'products.brand_id',
            'products.sub_category_id',
            'products.price',
            'products.thumbnail'
        ];
    }

    /**
     * Get relationships to eager load
     */
    protected static function getEagerLoadRelations(): array
    {
        return [
            'category:id,name',
            'brand:id,name',
            'subCategory:id,name'
        ];
    }

    public static function getGlobalSearchResultTitle(Model $record): string |  Htmlable
    {
        return $record->name;
    }

    public static function getGloballySearchableAttributes(): array
    {
        return [
            'name', // Nama produk (prioritas utama)
        ];
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()
            ->select(['id', 'name', 'status', 'price'])
            ->with(['category:id,name', 'brand:id,name'])
            ->where('status', '!=', 'deleted')
            ->limit(50); // Reduce limit for better performance
    }

    public static function modifyGlobalSearchQuery(Builder $query, string $search): void
    {
        // Clean and normalize search term
        $searchTerm = trim(strtolower($search));

        // If empty search, do nothing
        if (empty($searchTerm)) {
            return;
        }

        // Override default search behavior to search for exact phrase only
        $query->where(function ($query) use ($searchTerm) {
            $query->whereRaw('LOWER(name) LIKE ?', ['%' . $searchTerm . '%']);
        });
    }
    public static function getGlobalSearchResultDetails(\Illuminate\Database\Eloquent\Model $record): array
    {
        // Use cached availability status instead of complex queries
        $cacheKey = "product_search_details_{$record->id}";

        return \Illuminate\Support\Facades\Cache::remember($cacheKey, now()->addMinutes(5), function () use ($record) {
            $today = Carbon::now();

            // Simplified query with select to reduce data transfer
            $latestTransaction = $record->detailTransactions()
                ->select(['id', 'transaction_id', 'created_at'])
                ->with('transaction:id,start_date,end_date,booking_status')
                ->whereHas('transaction', function ($query) {
                    $query->where('booking_status', '!=', 'cancel');
                })
                ->latest('created_at')
                ->first();

            $transactionInfo = '-';
            if ($latestTransaction && $latestTransaction->transaction) {
                $startDate = $latestTransaction->transaction->start_date ? $latestTransaction->transaction->start_date->format('d M Y') : '-';
                $endDate = $latestTransaction->transaction->end_date ? $latestTransaction->transaction->end_date->format('d M Y') : '-';
                $transactionInfo = "{$startDate} - {$endDate}";
            }

            // Use model attribute instead of complex query
            $availabilityStatus = $record->is_available ? '🟢 Tersedia' : '🔴 Tidak Tersedia';

            return [
                'Latest Rental' => $transactionInfo,
                'Available' => $availabilityStatus,
            ];
        });
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
                \Filament\Forms\Components\FileUpload::make('thumbnail')
                    ->label('Thumbnail Produk (Utama)')
                    ->image()
                    ->directory('product-thumbnails')
                    ->visibility('public')
                    ->imageEditor()
                    ->imageEditorAspectRatios(['16:9', '4:3', '1:1'])
                    ->imageResizeTargetWidth('800')
                    ->imageResizeTargetHeight('600')
                    ->maxSize(10240) // 10MB (will be compressed)
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->helperText('Upload satu foto utama untuk thumbnail produk. File akan dikompres otomatis.')
                    ->saveUploadedFileUsing(function ($file, $record, $set, $get) {
                        $compressionService = new \App\Services\ImageCompressionService();
                        $compressedPath = $compressionService->compressAndStore($file, 'product-thumbnails');
                        return $compressedPath;
                    })
                    ->nullable(),

                \Filament\Forms\Components\FileUpload::make('product_photos')
                    ->label('Galeri Foto Produk')
                    ->image()
                    ->multiple()
                    ->directory('product-photos')
                    ->visibility('public')
                    ->imageEditor()
                    ->imageEditorAspectRatios(['16:9', '4:3', '1:1'])
                    ->imageResizeTargetWidth('1920')
                    ->imageResizeTargetHeight('1080')
                    ->maxSize(10240) // 10MB per file (will be compressed)
                    ->maxFiles(10) // Maximum 10 files
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->helperText('Upload beberapa foto sekaligus untuk galeri produk. Maksimal 10 file, 10MB per file. Foto akan dikompres otomatis.')
                    ->saveUploadedFileUsing(function ($file) {
                        $compressionService = new \App\Services\ImageCompressionService();
                        $compressedPath = $compressionService->compressAndStore($file, 'product-photos');
                        return $compressedPath;
                    })
                    ->dehydrated(false) // Don't save to product table
                    ->afterStateUpdated(function ($state, $record) {
                        // This will be handled in the create/update hooks
                    })
                    ->nullable()
                    ->columnSpanFull(),

                Forms\Components\Placeholder::make('existing_photos_display')
                    ->label('Foto yang Sudah Ada')
                    ->content(function ($record) {
                        if (! $record) return 'Belum ada foto yang diupload.';

                        $photos = $record->productPhotos;

                        if ($photos->isEmpty()) {
                            return 'Belum ada foto yang diupload.';
                        }

                        $photoHtml = '<div class="grid grid-cols-4 gap-4">';
                        foreach ($photos->take(8) as $photo) {
                            $imageUrl = asset('storage/' . $photo->photo);
                            $photoHtml .= '
                                <div class="relative">
                                    <img src="' . $imageUrl . '" 
                                         alt="Product Photo" 
                                         class="w-full h-10 object-cover rounded-lg border" />
                                    <div class="absolute top-1 right-1 bg-black bg-opacity-50 text-white text-xs px-1 rounded">
                                        #' . $photo->id . '
                                    </div>
                                </div>';
                        }

                        if ($photos->count() > 8) {
                            $remaining = $photos->count() - 8;
                            $photoHtml .= '<div class="flex items-center justify-center h-20 bg-gray-100 rounded-lg border">';
                            $photoHtml .= '<span class="text-gray-500 text-sm">+' . $remaining . ' lainnya</span>';
                            $photoHtml .= '</div>';
                        }

                        $photoHtml .= '</div>';
                        $photoHtml .= '<p class="text-sm text-gray-600 mt-2">Total: ' . $photos->count() . ' foto. Gunakan tab "Product Photos" untuk mengelola foto.</p>';

                        return new \Illuminate\Support\HtmlString($photoHtml);
                    })
                    ->visible(fn($record) => $record !== null)
                    ->columnSpanFull(),
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
            ->paginationPageOptions([10, 25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->deferLoading()
            ->poll('30s')
            ->headerActions([
                ImportAction::make()
                    ->importer(ProductImporter::class)
                    ->label('Import Products')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('primary')
                    ->options([
                        'updateExisting' => false,
                    ])
                    ->modalHeading('Import Products')
                    ->modalDescription('Upload an Excel file to import products. Make sure your file has the correct format.')
                    ->modalSubmitActionLabel('Import')
                    ->successNotificationTitle('Products imported successfully'),

                ExportAction::make()
                    ->exporter(ProductExporter::class)
                    ->label('Export Products')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->modalHeading('Export Products')
                    ->modalDescription('Export all products to an Excel file.')
                    ->modalSubmitActionLabel('Export')
                    ->fileName(fn(): string => 'products-' . date('Y-m-d-H-i-s'))
                    ->successNotificationTitle('Products exported successfully'),
            ])

            ->columns([
                ColumnGroup::make(
                    '',
                    [
                        Tables\Columns\TextColumn::make('name')
                            ->searchable()
                            ->wrap()
                            ->alignCenter()
                            ->sortable()
                            ->limit(50),
                        Tables\Columns\TextColumn::make('items_count')
                            ->label('Qty')
                            ->alignCenter()
                            ->sortable()
                            ->getStateUsing(function ($record) {
                                return $record->items_count ?? 0;
                            }),
                        Tables\Columns\ImageColumn::make('productPhotos.0.photo')
                            ->label('Foto')
                            ->circular()
                            ->size(50)
                            ->alignCenter(),


                        // Tables\Columns\TextColumn::make('items.serial_number')
                        //     ->label('Nomor Seri')
                        //     ->formatStateUsing(function ($record) {
                        //         $serialNumbers = $record->items->pluck('serial_number')->toArray();

                        //         if (count($serialNumbers) === 0) {
                        //             return '-';
                        //         }

                        //         if (count($serialNumbers) <= 5) {
                        //             return implode(', ', $serialNumbers);
                        //         }

                        //         $first5 = array_slice($serialNumbers, 0, 5);
                        //         $remaining = count($serialNumbers) - 5;

                        //         return implode(', ', $first5) . " <span style='color: #6b7280; font-style: italic;'>dan {$remaining} lainnya</span>";
                        //     })
                        //     ->html()
                        //     ->searchable()
                        //     ->wrap()
                        //     ->sortable(),

                        // Tables\Columns\TextColumn::make('price')
                        //     ->formatStateUsing(fn($state) => 'Rp' . number_format($state, 0, ',', '.'))
                        //     ->searchable()
                        //     ->sortable(),
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
            ->modifyQueryUsing(function (Builder $query) {
                // Use optimized repository query
                return static::getRepository()
                    ->optimized()
                    ->query()
                    ->select(static::getSelectColumns())
                    ->withCount(['items as items_count'])
                    ->with(static::getEagerLoadRelations())
                    ->orderBy('products.name');
            })



            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'available' => 'Available',
                        'unavailable' => 'Unavailable',
                        'maintenance' => 'Maintenance'
                    ])
                    ->label('Status')
                    ->multiple()
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Kategori')
                    ->options(function () {
                        return ResourceCacheService::cacheFilterOptions(
                            'filter_options_categories',
                            \App\Models\Category::query()
                        );
                    })
                    ->searchable()
                    ->multiple()
                    ->preload(),
                Tables\Filters\SelectFilter::make('brand_id')
                    ->label('Brand')
                    ->options(function () {
                        return ResourceCacheService::cacheFilterOptions(
                            'filter_options_brands',
                            \App\Models\Brand::query()
                        );
                    })
                    ->searchable()
                    ->multiple()
                    ->preload(),
                Tables\Filters\SelectFilter::make('sub_category_id')
                    ->label('Sub Kategori')
                    ->options(function () {
                        return ResourceCacheService::cacheFilterOptions(
                            'filter_options_sub_categories',
                            \App\Models\SubCategory::query()
                        );
                    })
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

                Tables\Actions\BulkAction::make('exportSelected')
                    ->label('Export Selected')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('info')
                    ->action(function ($records) {
                        $service = new ProductImportExportService();
                        $productIds = $records->pluck('id')->toArray();
                        $filePath = $service->exportProducts($productIds);
                        return response()->download($filePath, 'products_selected_export_' . date('Y-m-d_H-i-s') . '.xlsx')->deleteFileAfterSend();
                    }),
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
