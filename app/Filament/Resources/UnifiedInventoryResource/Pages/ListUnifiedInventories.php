<?php

namespace App\Filament\Resources\UnifiedInventoryResource\Pages;

use App\Filament\Resources\UnifiedInventoryResource;
use App\Models\Product;
use App\Models\Bundling;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Section;
use Filament\Notifications\Notification;

class ListUnifiedInventories extends ListRecords
{
    protected static string $resource = UnifiedInventoryResource::class;
    
    public function getTitle(): string
    {
        $selectedProducts = request('selected_products', []);
        $selectedBundlings = request('selected_bundlings', []);
        
        if (empty($selectedProducts) && empty($selectedBundlings)) {
            return 'Product & Bundling Availability';
        }
        
        $productCount = count($selectedProducts);
        $bundlingCount = count($selectedBundlings);
        
        $parts = [];
        if ($productCount > 0) {
            $parts[] = "{$productCount} Produk";
        }
        if ($bundlingCount > 0) {
            $parts[] = "{$bundlingCount} Bundling";
        }
        
        return 'Ketersediaan: ' . implode(' & ', $parts);
    }

    
    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('search_inventory')
                ->label('🔍 Cari Ketersediaan')
                ->icon('heroicon-o-magnifying-glass')
                ->color('primary')
                ->size('lg')
                ->form([
                    Section::make('🔍 Pencarian Ketersediaan Produk & Bundling')
                        ->description('Pilih produk atau bundling yang ingin Anda periksa ketersediaannya, lalu tentukan periode tanggal.')
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    Select::make('selected_products')
                                        ->label('🛍️ Pilih Produk')
                                        ->multiple()
                                        ->searchable()
                                        ->preload()
                                        ->options(function () {
                                            return Product::select('id', 'name')
                                                ->where('status', '!=', 'deleted')
                                                ->orderBy('name')
                                                ->pluck('name', 'id');
                                        })
                                        ->placeholder('Ketik untuk mencari produk...')
                                        ->helperText('💡 Anda bisa memilih beberapa produk sekaligus')
                                        ->columnSpan(1),
                                        
                                    Select::make('selected_bundlings')
                                        ->label('📦 Pilih Bundling')
                                        ->multiple()
                                        ->searchable()
                                        ->preload()
                                        ->options(function () {
                                            return Bundling::select('id', 'name')
                                                ->orderBy('name')
                                                ->pluck('name', 'id');
                                        })
                                        ->placeholder('Ketik untuk mencari bundling...')
                                        ->helperText('💡 Anda bisa memilih beberapa bundling sekaligus')
                                        ->columnSpan(1),
                                ]),
                                
                            Grid::make(2)
                                ->schema([
                                    DateTimePicker::make('start_date')
                                        ->label('📅 Tanggal & Waktu Mulai')
                                        ->default(now())
                                        ->maxDate(now()->addYear())
                                        ->native(false)
                                        ->displayFormat('d M Y H:i')
                                        ->helperText('Tanggal mulai periode pengecekan')
                                        ->required()
                                        ->columnSpan(1),
                                        
                                    DateTimePicker::make('end_date')
                                        ->label('📅 Tanggal & Waktu Selesai')
                                        ->default(now()->addDays(7)->endOfDay())
                                        ->after('start_date')
                                        ->maxDate(now()->addYear())
                                        ->native(false)
                                        ->displayFormat('d M Y H:i')
                                        ->helperText('Default: 7 hari kedepan jam 24:00')
                                        ->required()
                                        ->columnSpan(1),
                                ]),
                        ])
                        ->collapsible()
                        ->persistCollapsed(false),
                ])
                ->action(function (array $data) {
                    if (empty($data['selected_products']) && empty($data['selected_bundlings'])) {
                        Notification::make()
                            ->title('⚠️ Pilihan Kosong')
                            ->body('Silakan pilih minimal satu produk atau bundling.')
                            ->warning()
                            ->send();
                        return;
                    }
                    
                    // Build query parameters
                    $params = [];
                    
                    // Handle products array
                    if (!empty($data['selected_products'])) {
                        foreach ($data['selected_products'] as $productId) {
                            $params['selected_products[]'] = $productId;
                        }
                    }
                    
                    // Handle bundlings array  
                    if (!empty($data['selected_bundlings'])) {
                        foreach ($data['selected_bundlings'] as $bundlingId) {
                            $params['selected_bundlings[]'] = $bundlingId;
                        }
                    }
                    
                    // Add date parameters
                    $params['start_date'] = $data['start_date'];
                    $params['end_date'] = $data['end_date'];
                    
                    // Show success notification
                    $productCount = count($data['selected_products'] ?? []);
                    $bundlingCount = count($data['selected_bundlings'] ?? []);
                    
                    Notification::make()
                        ->title('✅ Pencarian Berhasil')
                        ->body("Menampilkan {$productCount} produk dan {$bundlingCount} bundling.")
                        ->success()
                        ->send();
                    
                    // Redirect with parameters
                    return redirect()->to('/admin/unified-inventory?' . http_build_query($params));
                })
                ->modalSubmitActionLabel('🔍 Cari Sekarang')
                ->modalWidth('4xl'),
                
            Actions\Action::make('reset_search')
                ->label('Reset')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->url('/admin/unified-inventory')
                ->visible(function () {
                    return !empty(request('selected_products')) || !empty(request('selected_bundlings'));
                }),
                
            Actions\Action::make('help')
                ->label('Help')
                ->icon('heroicon-o-question-mark-circle')
                ->color('info')
                ->modalHeading('Product & Bundling Availability Help')
                ->modalContent(view('filament.pages.product-availability-help'))
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Close'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'products' => Tab::make('Products')
                ->icon('heroicon-o-cube-transparent')
                ->badge(Product::count())
                ->modifyQueryUsing(fn (Builder $query) => $query),
                
            'bundlings' => Tab::make('Bundlings')
                ->icon('heroicon-o-cube')
                ->badge(Bundling::count())
                ->modifyQueryUsing(function (Builder $query) {
                    // Since we can't easily change the model, we'll handle this differently
                    // For now, show products but with bundling info
                    return $query;
                }),
                
            'all' => Tab::make('All')
                ->icon('heroicon-o-squares-2x2')
                ->badge(Product::count() + Bundling::count())
                ->modifyQueryUsing(fn (Builder $query) => $query),
        ];
    }

    /**
     * Get table records with bundling support
     */
    public function getTableRecords(): Paginator
    {
        $activeTab = $this->activeTab ?? 'products';
        $selectedProducts = request('selected_products', []);
        $selectedBundlings = request('selected_bundlings', []);
        
        // If bundlings tab is active and bundlings are selected
        if ($activeTab === 'bundlings' && !empty($selectedBundlings)) {
            // Get selected bundlings only
            $bundlings = Bundling::with(['products.items'])
                ->whereIn('id', $selectedBundlings)
                ->paginate(50);

            // Transform bundlings to have product-like structure
            $bundlings->getCollection()->transform(function ($bundling) {
                $bundling->item_type = 'Bundle';
                $bundling->bundle_description = $this->getBundlingDescription($bundling);
                return $bundling;
            });

            return $bundlings;
        }
        
        // For all other cases (products tab or no selections), use parent method
        return parent::getTableRecords();
    }

    /**
     * Get bundling description
     */
    public function getBundlingDescription(Bundling $bundling): string
    {
        $productNames = $bundling->products->pluck('name')->take(3)->implode(', ');
        $remaining = $bundling->products->count() - 3;
        $suffix = $remaining > 0 ? " (+{$remaining} more)" : '';
        return "Contains: {$productNames}{$suffix}";
    }

    /**
     * Override table columns based on active tab
     */
    public function getTableColumns(): array
    {
        $activeTab = $this->activeTab ?? 'products';
        $selectedBundlings = request('selected_bundlings', []);
        
        if ($activeTab === 'bundlings' && !empty($selectedBundlings)) {
            return $this->getBundlingTableColumns();
        }
        
        return parent::getTableColumns();
    }

    /**
     * Get bundling-specific table columns
     */
    public function getBundlingTableColumns(): array
    {
        return [
            \Filament\Tables\Columns\TextColumn::make('item_type')
                ->label('Type')
                ->getStateUsing(function ($record) {
                    return 'Bundle';
                })
                ->badge()
                ->color('success'),

            \Filament\Tables\Columns\TextColumn::make('name')
                ->label('Name')
                ->searchable()
                ->sortable()
                ->weight(\Filament\Support\Enums\FontWeight::SemiBold)
                ->wrap()
                ->suffix(' (Bundle)')
                ->description(fn ($record) => $record->bundle_description ?? ''),

            \Filament\Tables\Columns\TextColumn::make('total_bundles')
                ->label('Total Bundles')
                ->getStateUsing(function ($record) {
                    // Calculate minimum bundles possible
                    $minAvailable = null;
                    foreach ($record->products as $product) {
                        $requiredPerBundle = $product->pivot->quantity ?? 1;
                        $totalItems = $product->items()->count();
                        $maxBundlesFromThisProduct = intdiv($totalItems, $requiredPerBundle);
                        
                        $minAvailable = is_null($minAvailable) 
                            ? $maxBundlesFromThisProduct 
                            : min($minAvailable, $maxBundlesFromThisProduct);
                    }
                    return $minAvailable ?? 0;
                })
                ->alignCenter(),

            \Filament\Tables\Columns\TextColumn::make('available_bundles')
                ->label('Available Bundles')
                ->getStateUsing(function ($record) {
                    $startDate = request('start_date');
                    $endDate = request('end_date');

                    if (!$startDate || !$endDate) {
                        $startDate = now()->format('Y-m-d H:i:s');
                        $endDate = now()->addDays(7)->endOfDay()->format('Y-m-d H:i:s');
                    }

                    return $record->getAvailableQuantityForPeriod(
                        Carbon::parse($startDate),
                        Carbon::parse($endDate)
                    );
                })
                ->alignCenter()
                ->color(function ($state, $record) {
                    // Get total bundles
                    $minAvailable = null;
                    foreach ($record->products as $product) {
                        $requiredPerBundle = $product->pivot->quantity ?? 1;
                        $totalItems = $product->items()->count();
                        $maxBundlesFromThisProduct = intdiv($totalItems, $requiredPerBundle);
                        
                        $minAvailable = is_null($minAvailable) 
                            ? $maxBundlesFromThisProduct 
                            : min($minAvailable, $maxBundlesFromThisProduct);
                    }
                    
                    $total = $minAvailable ?? 1;
                    if ($total == 0) return 'gray';
                    $percentage = ($state / $total) * 100;
                    if ($percentage >= 70) return 'success';
                    if ($percentage >= 30) return 'warning';
                    return 'danger';
                })
                ->weight(\Filament\Support\Enums\FontWeight::Bold),

            \Filament\Tables\Columns\TextColumn::make('current_bundle_rentals')
                ->label('Current Rentals')
                ->getStateUsing(function ($record) {
                    $startDate = request('start_date');
                    $endDate = request('end_date');

                    if (!$startDate || !$endDate) {
                        $startDate = now()->format('Y-m-d H:i:s');
                        $endDate = now()->addDays(7)->endOfDay()->format('Y-m-d H:i:s');
                    }

                    return $record->detailTransactions()
                        ->whereHas('transaction', function ($query) use ($startDate, $endDate) {
                            $query->whereIn('booking_status', ['booking', 'paid', 'on_rented'])
                                ->where(function ($q) use ($startDate, $endDate) {
                                    $q->whereBetween('start_date', [$startDate, $endDate])
                                        ->orWhereBetween('end_date', [$startDate, $endDate])
                                        ->orWhere(function ($q2) use ($startDate, $endDate) {
                                            $q2->where('start_date', '<=', $startDate)
                                                ->where('end_date', '>=', $endDate);
                                        });
                                });
                        })
                        ->sum('quantity');
                })
                ->alignCenter()
                ->color('warning')
                ->icon('heroicon-o-clock'),
        ];
    }
}
