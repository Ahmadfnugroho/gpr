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
use App\Filament\Widgets\InventorySelectionWidget;

class ListUnifiedInventories extends ListRecords
{
    protected static string $resource = UnifiedInventoryResource::class;
    
    protected function getHeaderWidgets(): array
    {
        return [
            InventorySelectionWidget::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('switch_to_bundlings')
                ->label('Show Bundlings')
                ->icon('heroicon-o-cube')
                ->color('success')
                ->url(function () {
                    return '/admin/unified-inventory?tab=bundlings';
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
