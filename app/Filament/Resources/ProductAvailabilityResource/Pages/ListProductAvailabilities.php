<?php

namespace App\Filament\Resources\ProductAvailabilityResource\Pages;

use App\Filament\Resources\ProductAvailabilityResource;
use App\Models\ProductAvailability;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator as PaginatorAlias;

class ListProductAvailabilities extends ListRecords
{
    protected static string $resource = ProductAvailabilityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('refresh')
                ->label('Refresh Data')
                ->icon('heroicon-m-arrow-path')
                ->action('refreshData')
                ->color('gray'),
                
            Actions\Action::make('export')
                ->label('Export to CSV')
                ->icon('heroicon-m-document-arrow-down')
                ->action('exportData')
                ->color('success')
                ->disabled(), // Can be enabled later
        ];
    }

    /**
     * Override the table query to provide custom data
     */
    protected function getTableQuery(): ?Builder
    {
        // Return empty query since we'll handle data in getTableRecords
        return ProductAvailability::query()->whereRaw('1 = 0');
    }

    /**
     * Provide custom records for the table
     */
    public function getTableRecords(): EloquentCollection|Paginator|CursorPaginator
    {
        $searchTerm = $this->getTableSearch();
        
        // Get all availability data from our virtual model
        $records = ProductAvailability::getAllAvailabilityData($searchTerm);

        // Handle sorting
        $sortColumn = $this->getTableSortColumn();
        $sortDirection = $this->getTableSortDirection();

        if ($sortColumn && $sortColumn !== '__fake__') {
            $records = $records->sortBy($sortColumn, SORT_REGULAR, $sortDirection === 'desc');
        } else {
            // Default sort by name
            $records = $records->sortBy('name');
        }

        // Handle pagination
        $perPage = $this->getTableRecordsPerPage();
        $page = request('page', 1);
        $total = $records->count();
        
        $items = $records->forPage($page, $perPage)->values();

        return new LengthAwarePaginator(
            items: $items,
            total: $total,
            perPage: $perPage,
            currentPage: $page,
            options: [
                'path' => request()->url(),
                'pageName' => 'page',
            ]
        );
    }

    /**
     * Override the search functionality
     */
    public function updatedTableSearch(): void
    {
        $this->resetPage();
        // The search will be handled in getTableRecords method
    }

    /**
     * Custom action to refresh data
     */
    public function refreshData(): void
    {
        $this->resetPage();
        $this->resetTable();
        
        // Clear any cached data if needed
        // cache()->forget('product_availability_data');
        
        $this->notify('success', 'Data refreshed successfully!');
    }

    /**
     * Custom action to export data
     */
    public function exportData(): void
    {
        // Implementation for CSV export can be added here
        $this->notify('info', 'Export functionality will be available soon.');
    }

    /**
     * Get the title for this page
     */
    public function getTitle(): string
    {
        return 'Product Availability Search';
    }

    /**
     * Get the heading for this page
     */
    public function getHeading(): string
    {
        $filters = $this->getTableFilters();
        $dateFilter = $filters['date_range'] ?? [];
        
        $startDate = $dateFilter['start_date'] ?? now()->format('Y-m-d');
        $endDate = $dateFilter['end_date'] ?? now()->addDays(7)->format('Y-m-d');
        
        return 'Product Availability - ' . 
               \Carbon\Carbon::parse($startDate)->format('M d') . ' to ' . 
               \Carbon\Carbon::parse($endDate)->format('M d, Y');
    }

    /**
     * Get the subheading for this page
     */
    public function getSubheading(): ?string
    {
        return 'Real-time availability search for products and bundlings. Use the date filter to check availability for specific periods.';
    }
}
