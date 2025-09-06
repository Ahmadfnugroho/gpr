<?php

namespace App\Filament\Resources\InventoryAvailabilityResource\Pages;

use App\Filament\Resources\InventoryAvailabilityResource;
use App\Models\Product;
use App\Models\Bundling;
use App\Models\InventoryAvailability;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Model;

class ListInventoryAvailabilities extends ListRecords
{
    protected static string $resource = InventoryAvailabilityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action since this is read-only
        ];
    }

    /**
     * Get custom table query that combines Products and Bundlings
     */
    protected function getTableQuery(): ?Builder
    {
        // For now, just return products - we'll enhance this in the table columns
        return Product::query()->with(['items', 'bundlings']);
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Items')
                ->modifyQueryUsing(function (Builder $query) {
                    // Show products - bundlings will be added via separate logic
                    return $query;
                }),
            'products' => Tab::make('Products Only')
                ->modifyQueryUsing(function (Builder $query) {
                    return $query; // Already showing products
                }),
            'bundlings' => Tab::make('Bundlings Only')
                ->modifyQueryUsing(function (Builder $query) {
                    // We'll need to handle this differently since we can't easily switch model in tabs
                    return $query->whereRaw('1=0'); // Return empty for now
                }),
        ];
    }
}
