<?php

namespace App\Filament\Resources\ProductAvailabilityResource\Pages;

use App\Filament\Resources\ProductAvailabilityResource;
use App\Models\ProductAvailability;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator as PaginatorAlias;
use Filament\Notifications\Notification;

class ListProductAvailabilities extends ListRecords implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = ProductAvailabilityResource::class;
    
    public ?array $data = [];
    
    public function mount(): void
    {
        parent::mount();
        
        // Pre-populate form from URL parameters (from widget)
        $selectedItems = [];
        
        // Handle products from URL
        $selectedProducts = request('selected_products', []);
        if (is_array($selectedProducts) && !empty($selectedProducts)) {
            foreach ($selectedProducts as $productId) {
                $selectedItems[] = "product-{$productId}";
            }
        }
        
        // Handle bundlings from URL
        $selectedBundlings = request('selected_bundlings', []);
        if (is_array($selectedBundlings) && !empty($selectedBundlings)) {
            foreach ($selectedBundlings as $bundlingId) {
                $selectedItems[] = "bundling-{$bundlingId}";
            }
        }
        
        // Initialize form data
        $this->data = [
            'selected_items' => $selectedItems,
            'start_date' => request('start_date', now()->format('Y-m-d H:i:s')),
            'end_date' => request('end_date', now()->addDays(7)->endOfDay()->format('Y-m-d H:i:s')),
        ];
        
        // Fill the form with data
        $this->form->fill($this->data);
    }
    
    public function form(\Filament\Forms\Form $form): \Filament\Forms\Form
    {
        return ProductAvailabilityResource::form($form)->statePath('data');
    }
    
    protected function getViewData(): array
    {
        return array_merge(parent::getViewData(), [
            'form' => $this->form,
        ]);
    }
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
        $filters = $this->getTableFilters();
        
        // Get selected items from filter
        $selectedItems = $filters['item_selection']['selected_items'] ?? [];
        
        // Get filtered availability data
        $records = $this->getFilteredAvailabilityData($searchTerm, $selectedItems);

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
        // Check URL parameters first (from widget or form)
        $urlStartDate = request('start_date');
        $urlEndDate = request('end_date');
        $urlProducts = request('selected_products', []);
        $urlBundlings = request('selected_bundlings', []);
        
        if ($urlStartDate && $urlEndDate) {
            $startDate = \Carbon\Carbon::parse($urlStartDate)->format('M d H:i');
            $endDate = \Carbon\Carbon::parse($urlEndDate)->format('M d H:i');
            
            $productCount = is_array($urlProducts) ? count($urlProducts) : 0;
            $bundlingCount = is_array($urlBundlings) ? count($urlBundlings) : 0;
            $totalCount = $productCount + $bundlingCount;
            
            return "📊 Availability Results ({$totalCount} items) - {$startDate} to {$endDate}";
        }
        
        // Fallback to filter parameters
        $filters = $this->getTableFilters();
        $dateFilter = $filters['date_range'] ?? [];
        
        $startDate = $dateFilter['start_date'] ?? now()->format('Y-m-d H:i');
        $endDate = $dateFilter['end_date'] ?? now()->addDays(7)->format('Y-m-d H:i');
        
        return '🔍 Product Availability Search';
    }

    /**
     * Get the subheading for this page
     */
    public function getSubheading(): ?string
    {
        return 'Real-time availability search for products and bundlings. Use filters to select specific items and date ranges.';
    }
    
    /**
     * Get filtered availability data based on selected items
     */
    protected function getFilteredAvailabilityData($searchTerm = null, $selectedItems = [])
    {
        // Check URL parameters first (from widget or form)
        $urlProducts = request('selected_products', []);
        $urlBundlings = request('selected_bundlings', []);
        
        // If URL has parameters, use those instead of filter parameters
        if (!empty($urlProducts) || !empty($urlBundlings)) {
            $selectedProducts = is_array($urlProducts) ? array_map('intval', $urlProducts) : [];
            $selectedBundlings = is_array($urlBundlings) ? array_map('intval', $urlBundlings) : [];
            
            return ProductAvailability::getSelectedAvailabilityData($searchTerm, $selectedProducts, $selectedBundlings);
        }
        
        // If no URL params, check filter params
        if (!empty($selectedItems)) {
            // Parse selected items to separate products and bundlings
            $selectedProducts = [];
            $selectedBundlings = [];
            
            foreach ($selectedItems as $item) {
                if (str_starts_with($item, 'product-')) {
                    $selectedProducts[] = (int) str_replace('product-', '', $item);
                } elseif (str_starts_with($item, 'bundling-')) {
                    $selectedBundlings[] = (int) str_replace('bundling-', '', $item);
                }
            }
            
            return ProductAvailability::getSelectedAvailabilityData($searchTerm, $selectedProducts, $selectedBundlings);
        }
        
        // If no selection at all, return empty collection to show empty state
        return collect();
    }
    
    /**
     * Handle search action from the form
     */
    public function searchAction(): void
    {
        try {
            // Get form data
            $data = $this->form->getState();
            
            // Process the form data
            $selectedProducts = [];
            $selectedBundlings = [];
            
            if (!empty($data['selected_items'])) {
                foreach ($data['selected_items'] as $item) {
                    if (str_starts_with($item, 'product-')) {
                        $selectedProducts[] = str_replace('product-', '', $item);
                    } elseif (str_starts_with($item, 'bundling-')) {
                        $selectedBundlings[] = str_replace('bundling-', '', $item);
                    }
                }
            }
            
            // Build parameters
            $params = [];
            if (!empty($selectedProducts)) {
                $params['selected_products'] = $selectedProducts;
            }
            if (!empty($selectedBundlings)) {
                $params['selected_bundlings'] = $selectedBundlings;
            }
            if (!empty($data['start_date'])) {
                $params['start_date'] = $data['start_date'];
            }
            if (!empty($data['end_date'])) {
                $params['end_date'] = $data['end_date'];
            }
            
            // Show success notification
            $productCount = count($selectedProducts);
            $bundlingCount = count($selectedBundlings);
            $totalCount = $productCount + $bundlingCount;
            
            if ($totalCount > 0) {
                Notification::make()
                    ->title('✅ Search Completed')
                    ->body("Showing availability for {$productCount} products and {$bundlingCount} bundlings.")
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('⚠️ No Items Selected')
                    ->body('Please select at least one product or bundling to check availability.')
                    ->warning()
                    ->send();
                return;
            }
            
            // Build URL with parameters
            $url = route('filament.admin.resources.product-availability.index');
            if (!empty($params)) {
                $url .= '?' . http_build_query($params);
            }
            
            // Redirect with parameters
            $this->redirect($url);
            
        } catch (\Exception $e) {
            Notification::make()
                ->title('❌ Search Failed')
                ->body('An error occurred while processing your search. Please try again.')
                ->danger()
                ->send();
                
            \Log::error('ProductAvailability search error: ' . $e->getMessage());
        }
    }
}
