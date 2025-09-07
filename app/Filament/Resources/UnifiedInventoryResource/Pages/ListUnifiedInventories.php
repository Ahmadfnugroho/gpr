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

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Items')
                ->icon('heroicon-o-squares-2x2')
                ->badge(Product::count())
                ->modifyQueryUsing(fn (Builder $query) => $query),
        ];
    }

    /**
     * Get table records - simplified to always show products
     */
    public function getTableRecords(): Paginator
    {
        // Always use parent method to show products
        return parent::getTableRecords();
    }

}
