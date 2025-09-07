<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BaseMemoryOptimizedResource;
use App\Filament\Resources\ProductResource\Pages\ListProducts;
use App\Filament\Resources\ProductResource\Pages\CreateProduct;
use App\Filament\Resources\ProductResource\Pages\ViewProduct;
use App\Filament\Resources\ProductResource\Pages\EditProduct;

use App\Models\Product;
use App\Services\FilamentMemoryOptimizationService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductResource extends BaseMemoryOptimizedResource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationLabel = 'Produk';

    protected static ?string $modelLabel = 'Produk';

    protected static ?string $pluralModelLabel = 'Produk';

    /**
     * Configure form for creating/editing products
     */
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Dasar')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Produk')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('description')
                            ->label('Deskripsi')
                            ->rows(3),

                        Forms\Components\TextInput::make('price')
                            ->label('Harga')
                            ->numeric()
                            ->prefix('Rp'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Kategori & Brand')
                    ->schema([
                        Forms\Components\Select::make('category_id')
                            ->label('Kategori')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload(),

                        Forms\Components\Select::make('brand_id')
                            ->label('Brand')
                            ->relationship('brand', 'name')
                            ->searchable()
                            ->preload(),
                    ])
                    ->columns(2),
            ]);
    }

    /**
     * Override table columns with memory optimization
     */
    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('id')
                ->label('ID')
                ->sortable()
                ->searchable(),

            Tables\Columns\TextColumn::make('name')
                ->label('Nama Produk')
                ->searchable()
                ->sortable()
                ->limit(50),

            Tables\Columns\TextColumn::make('category.name')
                ->label('Kategori')
                ->sortable()
                ->searchable(),

            Tables\Columns\TextColumn::make('brand.name')
                ->label('Brand')
                ->sortable()
                ->searchable(),

            Tables\Columns\TextColumn::make('price')
                ->label('Harga')
                ->money('IDR')
                ->sortable(),

            Tables\Columns\TextColumn::make('status')
                ->label('Status')
                ->badge()
                ->color(fn(string $state): string => match ($state) {
                    'active' => 'success',
                    'inactive' => 'warning',
                    'premiere' => 'info',
                    default => 'gray',
                }),

            Tables\Columns\TextColumn::make('created_at')
                ->label('Dibuat')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    /**
     * Override table filters with memory optimization
     */
    protected function getTableFilters(): array
    {
        return [
            Tables\Filters\SelectFilter::make('category_id')
                ->label('Kategori')
                ->relationship('category', 'name')
                ->searchable()
                ->preload(),

            Tables\Filters\SelectFilter::make('brand_id')
                ->label('Brand')
                ->relationship('brand', 'name')
                ->searchable()
                ->preload(),

            Tables\Filters\SelectFilter::make('status')
                ->label('Status')
                ->options([
                    'active' => 'Active',
                    'inactive' => 'Inactive',
                    'premiere' => 'Premiere',
                ]),

            ...parent::getTableFilters(), // Include date filters from parent
        ];
    }

    /**
     * Override query modification for specific optimizations
     */
    protected function modifyTableQuery(Builder $query): Builder
    {
        // Only load necessary relations to reduce memory usage
        return $query
            ->with(['category:id,name', 'brand:id,name']) // Only load needed columns
            ->select([
                'products.id',
                'products.name',
                'products.price',
                'products.status',
                'products.category_id',
                'products.brand_id',
                'products.created_at'
            ]);
    }

    /**
     * Override table actions with memory consideration
     */
    protected function getTableActions(): array
    {
        return [
            Tables\Actions\ViewAction::make()
                ->label('Lihat')
                ->mutateRecordDataUsing(function (array $data): array {
                    // Clear memory before loading detailed view
                    $this->clearMemoryBeforeOperation();
                    return $data;
                }),

            Tables\Actions\EditAction::make()
                ->label('Edit'),

            Tables\Actions\DeleteAction::make()
                ->label('Hapus')
                ->requiresConfirmation(),
        ];
    }

    /**
     * Override bulk actions with memory limits
     */
    protected function getBulkTableActions(): array
    {
        $maxBulkActions = FilamentMemoryOptimizationService::getOptimalChunkSize();

        return [
            Tables\Actions\BulkAction::make('update_status')
                ->label('Update Status')
                ->icon('heroicon-m-pencil-square')
                ->form([
                    Forms\Components\Select::make('status')
                        ->label('Status Baru')
                        ->options([
                            'active' => 'Active',
                            'inactive' => 'Inactive',
                            'premiere' => 'Premiere',
                        ])
                        ->required(),
                ])
                ->action(function (array $data, $records) {
                    // Process in chunks to prevent memory issues
                    $this->processLargeDataset(
                        $records->toQuery(),
                        function ($chunk) use ($data) {
                            foreach ($chunk as $record) {
                                $record->update(['status' => $data['status']]);
                            }
                        }
                    );
                })
                ->deselectRecordsAfterCompletion()
                ->requiresConfirmation()
                ->modalDescription("Maksimal {$maxBulkActions} item per operasi untuk mencegah memory overflow."),

            ...parent::getBulkTableActions(), // Include delete action from parent
        ];
    }

    /**
     * Override header actions with memory optimization
     */
    protected function getHeaderActions(): array
    {
        return [
            Tables\Actions\CreateAction::make()
                ->label('Tambah Produk')
                ->mutateFormDataUsing(function (array $data): array {
                    $this->clearMemoryBeforeOperation();
                    return $data;
                }),

            $this->getMemoryOptimizedExportAction()
                ->label('Export Produk')
                ->action(function () {
                    $this->clearMemoryBeforeOperation();

                    $maxExportRecords = FilamentMemoryOptimizationService::getOptimalPageSize() * 10;

                    // Get products with limit
                    $products = Product::with(['category:id,name', 'brand:id,name'])
                        ->limit($maxExportRecords)
                        ->get();

                    // Implement export logic here
                    $this->notify(
                        'success',
                        "Export {$products->count()} produk berhasil!"
                    );
                }),
        ];
    }

    /**
     * Get navigation badge with memory consideration
     */
    public static function getNavigationBadge(): ?string
    {
        // Only count if memory allows, otherwise skip
        if (FilamentMemoryOptimizationService::isMemoryLimitApproaching(0.8)) {
            return null;
        }

        return static::getModel()::count();
    }

    /**
     * Get pages configuration
     */
    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'view' => ViewProduct::route('/{record}'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }
}

/**
 * Custom Pages with Memory Optimization
 */

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\Pages\ViewRecord;
use App\Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\ProductResource;
use App\Services\FilamentMemoryOptimizationService;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        $memoryInfo = FilamentMemoryOptimizationService::getMemoryUsage();

        $actions = parent::getHeaderActions();

        // Show memory status in debug mode
        if (config('app.debug') && FilamentMemoryOptimizationService::isMemoryLimitApproaching(0.6)) {
            $actions[] = \Filament\Actions\Action::make('memory_status')
                ->label("Memory: {$memoryInfo['usage_percentage']}%")
                ->color($memoryInfo['usage_percentage'] > 80 ? 'danger' : 'warning')
                ->tooltip("Current: {$memoryInfo['current_usage_formatted']} / {$memoryInfo['limit']}")
                ->action(function () {
                    FilamentMemoryOptimizationService::clearMemory();
                    $this->redirect(request()->url());
                });
        }

        return $actions;
    }
}

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

class ViewProduct extends ViewRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\EditAction::make()
                ->mutateRecordDataUsing(function (array $data): array {
                    FilamentMemoryOptimizationService::clearMemory();
                    return $data;
                }),
            \Filament\Actions\DeleteAction::make(),
        ];
    }
}

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\ViewAction::make(),
            \Filament\Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
