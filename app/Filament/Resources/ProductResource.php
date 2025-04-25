<?php

namespace App\Filament\Resources;

use App\Casts\MoneyCast;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Filament\Exports\ProductExporter;
use App\Filament\Imports\ProductImporter;
use App\Filament\Imorts\ProductPhotoImporter;
use App\Filament\Imports\ProductSpecificationImporter;
use App\Models\Category;
use App\Models\Brand;
use App\Models\SubCategory;
use App\Models\ProductPhoto;
use App\Models\RentalInclude;

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
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Columns\ColumnGroup;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Rmsramos\Activitylog\Actions\ActivityLogTimelineTableAction;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;
use Nette\Utils\Html;

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
        return parent::getGlobalSearchEloquentQuery()->with(['transactions', 'detailTransactions']);
    }
    public static function getGlobalSearchResultDetails(\Illuminate\Database\Eloquent\Model $record): array
    {






        // Ambil data transaksi terkait (jika ada)
        return [
            // 'jumlah barang' => $record->quantity ?? 'N/A',
            // 'Booking ID' => $record->transactions->map(function ($transaction) {
            //     return "{$transaction->booking_transaction_id}";
            // })->implode("\n"),
            // 'Booking Status' => $record->transactions->map(function ($transaction) {
            //     return "{$transaction->booking_status}";
            // })->implode("\n"),
            // 'Durasi' => $record->transactions->map(function ($transaction) {
            //     return "{$transaction->duration} Hari, " . $transaction->start_date->format('d M Y H:i') . " - " . $transaction->end_date->format('d M Y H:i');
            // })->implode("\n"),
            // 'jml' => $record->detailTransactions->map(function ($detailTransactions) {
            //     return "{$detailTransactions->quantity}";
            // })->implode("\n"),
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



                // Pastikan tidak negatif dan kembalikan sebagai string
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
                Forms\Components\TextInput::make('quantity')
                    ->label('Jumlah Produk')
                    ->required()
                    ->numeric()
                    ->reactive(),


                Forms\Components\TextInput::make('price')
                    ->label('Harga Produk')
                    ->required()
                    ->numeric()
                    ->prefix('Rp'),
                Forms\Components\FileUpload::make('thumbnail')
                    ->label('Foto Produk')
                    ->image()
                    ->nullable(),
                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options([
                        'available' => 'available',
                        'unavailable' => 'unavailable',
                    ])
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


                        Tables\Columns\TextColumn::make('quantity')
                            ->label('Qty')
                            ->wrap()
                            ->alignCenter()
                            ->wrapHeader()


                            ->searchable()
                            ->sortable(),
                        Tables\Columns\TextColumn::make('price')
                            ->formatStateUsing(fn($state) => 'Rp' . number_format($state, 0, ',', '.'))
                            ->searchable()
                            ->sortable(),
                        // Tables\Columns\IconColumn::make('status')
                        //     ->label('Status Ketersediaan')
                        //     ->sortable()
                        //     ->getStateUsing(fn ($record) => $record->status === 'available')
                        //     ->trueIcon('heroicon-o-check-circle')  // Ikon untuk status 'available'
                        //     ->falseIcon('heroicon-o-x-circle')    // Ikon untuk status 'unavailable'
                        //     ->trueColor('success')
                        //     ->falseColor('danger'),


                    ]
                ),


                ColumnGroup::make('Status', [
                    Tables\Columns\ToggleColumn::make('status')
                        ->label('status')
                        ->sortable()
                        ->getStateUsing(fn($record) => $record->status === 'available'),

                    Tables\Columns\ToggleColumn::make('premiere')
                        ->label('Featured')
                        ->sortable()
                        ->width('1%'),
                ])
                    ->alignment(Alignment::Center)
                    ->wrapHeader()






                // Product Slug Column

            ])


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
                    // Tables\Actions\BulkActionGroup::make([
                    //     Tables\Actions\Action::make('available')
                    //     ->icon('heroicon-o-check-circle')
                    //     ->color('success')
                    //     ->label('available')
                    //     ->requiresConfirmation()
                    //     ->action(function (Product $record) {
                    //         $record->update(['status' => 'available']);
                    //         Notification::make()
                    //             ->success()
                    //             ->title('Berhasil Mengubah Status Produk')
                    //             ->send();
                    //     }),
                    //     Tables\Actions\Action::make('unavailable')
                    //     ->icon('heroicon-o-x-circle')
                    //     ->color('danger')
                    //     ->label('unavailable')
                    //     ->requiresConfirmation()
                    //     ->action(function (Product $record) {
                    //         $record->update(['status' => 'unavailable']);
                    //         Notification::make()
                    //             ->success()
                    //             ->title('Berhasil Mengubah Status Product')
                    //             ->send();
                    //     })
                    // ])
                    // ->label('Ubah Status Produk'),
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
                ])
                    ->label('Select Product')
                    ->icon('heroicon-o-eye'),

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
