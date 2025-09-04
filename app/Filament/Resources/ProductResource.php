<?php

namespace App\Filament\Resources;


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
use Filament\Forms\Components\Checkbox;

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
        ];
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()
            ->with([
                'detailTransactions.transaction:id,start_date,end_date,booking_status'
            ])
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

        // Override default search behavior to search for exact phrase only
        $query->where(function ($query) use ($searchTerm) {
            $query->whereRaw('LOWER(name) LIKE ?', ['%' . $searchTerm . '%']);
        });
    }
    public static function getGlobalSearchResultDetails(\Illuminate\Database\Eloquent\Model $record): array
    {
        $today = Carbon::now();

        // Get latest transaction dates
        $latestTransaction = $record->detailTransactions()
            ->with('transaction')
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

        // Check availability based on current active transactions
        $hasActiveTransaction = $record->detailTransactions()
            ->whereHas('transaction', function ($query) use ($today) {
                $query->where('booking_status', '!=', 'cancel')
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
            ->defaultPaginationPageOption(50)
            ->headerActions([
                Action::make('downloadTemplate')
                    ->label('Download Template')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->action(function () {
                        $service = new ProductImportExportService();
                        $filePath = $service->generateTemplate();
                        return response()->download($filePath, 'product_import_template.xlsx')->deleteFileAfterSend();
                    }),
                    
                Action::make('import')
                    ->label('Import Excel')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('primary')
                    ->form([
                        FileUpload::make('excel_file')
                            ->label('Excel File')
                            ->acceptedFileTypes(['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'text/csv'])
                            ->required()
                            ->maxSize(2048)
                            ->helperText('Upload Excel file (.xls, .xlsx, .csv). Maximum 2MB'),
                        Checkbox::make('update_existing')
                            ->label('Update existing products (based on name)')
                            ->default(false)
                            ->helperText('If unchecked, products with existing names will be skipped')
                    ])
                    ->action(function (array $data) {
                        try {
                            $service = new ProductImportExportService();
                            $file = $data['excel_file'];
                            $updateExisting = $data['update_existing'] ?? false;
                            
                            // Convert to UploadedFile if needed
                            if (is_string($file)) {
                                $filePath = storage_path('app/public/' . $file);
                                $file = new \Illuminate\Http\UploadedFile(
                                    $filePath,
                                    basename($filePath),
                                    mime_content_type($filePath),
                                    null,
                                    true
                                );
                            }
                            
                            $results = $service->importProducts($file, $updateExisting);
                            
                            $message = "Import completed! Total: {$results['total']}, Success: {$results['success']}, Updated: {$results['updated']}, Failed: {$results['failed']}";
                            
                            if (!empty($results['errors'])) {
                                Notification::make()
                                    ->title('Import Completed with Errors')
                                    ->body($message . "\n\nErrors: " . implode(', ', array_slice($results['errors'], 0, 3)))
                                    ->warning()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('Import Successful')
                                    ->body($message)
                                    ->success()
                                    ->send();
                            }
                            
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Import Failed')
                                ->body('Error: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                    
                Action::make('export')
                    ->label('Export All')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('info')
                    ->action(function () {
                        $service = new ProductImportExportService();
                        $filePath = $service->exportProducts();
                        return response()->download($filePath, 'products_export_' . date('Y-m-d_H-i-s') . '.xlsx')->deleteFileAfterSend();
                    }),
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
                            ->formatStateUsing(function ($record) {
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
                
                Action::make('exportSelected')
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
