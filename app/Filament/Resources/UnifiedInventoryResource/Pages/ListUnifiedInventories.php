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
use App\Filament\Widgets\InventorySelectionFormWidget;

class ListUnifiedInventories extends ListRecords
{
    protected static string $resource = UnifiedInventoryResource::class;
    
    protected function getHeaderWidgets(): array
    {
        return [
            InventorySelectionFormWidget::class,
        ];
    }
    
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

    // Removed tabs to show all items in single view

    /**
     * Get table records - unified view for both products and bundlings
     */
    public function getTableRecords(): Paginator
    {
        $selectedProducts = request('selected_products', []);
        $selectedBundlings = request('selected_bundlings', []);
        
        // If no selections, return empty
        if (empty($selectedProducts) && empty($selectedBundlings)) {
            return new \Illuminate\Pagination\LengthAwarePaginator([], 0, 50);
        }
        
        $allItems = collect();
        
        // Get selected products
        if (!empty($selectedProducts)) {
            $products = Product::with(['items', 'bundlings', 'category', 'brand', 'subCategory'])
                ->whereIn('id', $selectedProducts)
                ->get();
            
            foreach ($products as $product) {
                $product->item_type = 'Product';
                $product->unified_type = 'product';
                $allItems->push($product);
            }
        }
        
        // Get selected bundlings and transform them to look like products
        if (!empty($selectedBundlings)) {
            $bundlings = Bundling::with(['products.items'])
                ->whereIn('id', $selectedBundlings)
                ->get();
            
            foreach ($bundlings as $bundling) {
                // Create a pseudo-product object for bundlings
                $bundlingAsProduct = new Product();
                $bundlingAsProduct->id = $bundling->id;
                $bundlingAsProduct->name = $bundling->name;
                $bundlingAsProduct->item_type = 'Bundle';
                $bundlingAsProduct->unified_type = 'bundling';
                $bundlingAsProduct->original_bundling = $bundling;
                
                // Calculate total items from all products in bundle
                $bundlingAsProduct->total_bundle_items = $bundling->products->sum(function($product) {
                    return $product->items()->count();
                });
                
                $allItems->push($bundlingAsProduct);
            }
        }
        
        // Paginate the collection
        $currentPage = request('page', 1);
        $perPage = 50;
        $currentItems = $allItems->slice(($currentPage - 1) * $perPage, $perPage)->values();
        
        return new \Illuminate\Pagination\LengthAwarePaginator(
            $currentItems,
            $allItems->count(),
            $perPage,
            $currentPage,
            ['path' => request()->url(), 'pageName' => 'page']
        );
    }

}
