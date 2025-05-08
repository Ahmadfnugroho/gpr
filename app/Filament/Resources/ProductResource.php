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
        return ['name', 'category.name', 'brand.name', 'subCategory.name']; // Hanya mencari berdasarkan nama produk
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        // Optimize query by eagerly loading related models
        return parent::getGlobalSearchEloquentQuery()->with(['transactions', 'detailTransactions', 'items']);
    }
    public static function getGlobalSearchResultDetails(\Illuminate\Database\Eloquent\Model $record): array
    {
        // Ambil data transaksi terkait (jika ada)
        return [
            '(status' => (function () use ($record) {
                // Jumlah barang yang tersedia
                $jumlahBarang = $record->quantity ?? 0;
                // Hitung rented quantity
                $today = Carbon::now();

                // Status transaksi yang termasuk dalam perhitungan
                $includedStatuses = ['rented', 'paid', 'pending'];

                // Hitung rented quantity
                $rentedQuantity = $record->detailTransactions
                    ->filter(function ($detailTransaction) use ($today, $includedStatuses) {
                        // Ambil start_date dan end_date dari transaksi
                        $startDates = $detailTransaction->transaction->pluck('start_date')->toArray();
                        $endDates = $detailTransaction->transaction->pluck('end_date')->toArray();

                        // Cek apakah ada transaksi yang sedang berlangsung
                        foreach ($startDates as $index => $startDate) {
                            $endDate = $endDates[$index];

                            // Parse tanggal ke objek Carbon
                            $startDate = Carbon::parse($startDate);
                            $endDate = Carbon::parse($endDate);

                            // Cek status transaksi dan rentang tanggal
                            if (
                                in_array($detailTransaction->transaction->booking_status, $includedStatuses) &&
                                $startDate <= $today &&
                                $endDate >= $today
                            ) {
                                return true; // Transaksi sedang berlangsung
                            }
                        }

                        return false; // Transaksi tidak sedang berlangsung
                    })
                    ->sum('quantity'); // Jumlahkan quantity


                // Hitung jumlah tersedia
                $jumlahTersedia = $record->quantity - $rentedQuantity;
                $status = $jumlahTersedia > 0 ? $record->status : 'unavailable';
                return "{$status}, Tersedia: {$jumlahTersedia})";
            })(), // Eksekusi Closure dan kembalikan nilainya
        ];
    }

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationGroup = 'Product';

    protected static ?string $navigationLabel = 'product';

    // protected static bool $shouldRegisterNavigation = false;

    protected static ?int $navigationSort = 24;

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
                    ->formatStateUsing(function (Product $record) {
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

                        $tersediaHtml = count($tersedia)
                            ? '<span style="color:green">Tersedia: ' . implode(', ', array_map('e', $tersedia)) . '</span>'
                            : '<span style="color:red">Tidak ada serial number tersedia</span>';

                        $tidakTersediaHtml = count($tidakTersedia)
                            ? '<br><span style="color:gray">Tidak Tersedia: ' . implode(', ', array_map('e', $tidakTersedia)) . '</span>'
                            : '';

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
                            ->formatStateUsing(fn($record) => $record->items->pluck('serial_number')->implode(', '))
                            ->searchable()
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
                Tables\Filters\SelectFilter::make('subcategory_id')
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
