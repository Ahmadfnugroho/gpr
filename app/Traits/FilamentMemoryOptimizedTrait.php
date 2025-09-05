<?php

namespace App\Traits;

use App\Services\FilamentMemoryOptimizationService;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\Paginator;

trait FilamentMemoryOptimizedTrait
{
    /**
     * Configure table with memory optimization
     */
    protected function configureMemoryOptimizedTable(Table $table): Table
    {
        $optimalPageSize = FilamentMemoryOptimizationService::getOptimalPageSize();
        
        return $table
            ->paginationPageOptions($this->getPaginationOptions())
            ->defaultPaginationPageOption($optimalPageSize)
            ->deferLoading() // Enable lazy loading
            ->poll('30s') // Reduce polling frequency
            ->emptyStateHeading('Tidak ada data')
            ->emptyStateDescription('Belum ada data yang dapat ditampilkan.')
            ->recordTitleAttribute('id')
            ->striped()
            ->defaultSort('id', 'desc');
    }
    
    /**
     * Get pagination options based on memory availability
     */
    protected function getPaginationOptions(): array
    {
        $optimal = FilamentMemoryOptimizationService::getOptimalPageSize();
        
        return [
            10,
            25,
            min(50, $optimal),
            min(100, $optimal * 2),
        ];
    }
    
    /**
     * Apply memory optimization to query
     */
    protected function applyMemoryOptimization(Builder $query): Builder
    {
        return FilamentMemoryOptimizationService::createFilamentTableQuery($query);
    }
    
    /**
     * Get memory-optimized table query
     */
    public function getTableQuery(): ?Builder
    {
        $query = static::getModel()::query();
        
        // Apply memory optimization
        $query = $this->applyMemoryOptimization($query);
        
        // Apply additional filters or modifications
        return $this->modifyTableQuery($query);
    }
    
    /**
     * Override this method to add custom query modifications
     */
    protected function modifyTableQuery(Builder $query): Builder
    {
        return $query;
    }
    
    /**
     * Get optimized columns for table
     */
    protected function getOptimizedTableColumns(): array
    {
        // Override this in your resource to define specific columns
        return [
            Tables\Columns\TextColumn::make('id')
                ->label('ID')
                ->sortable()
                ->searchable(),
            Tables\Columns\TextColumn::make('created_at')
                ->label('Dibuat')
                ->dateTime()
                ->sortable(),
        ];
    }
    
    /**
     * Get memory usage widget data
     */
    protected function getMemoryUsageData(): array
    {
        return FilamentMemoryOptimizationService::getMemoryUsage();
    }
    
    /**
     * Check if memory optimization warning should be shown
     */
    protected function shouldShowMemoryWarning(): bool
    {
        return FilamentMemoryOptimizationService::isMemoryLimitApproaching(0.75);
    }
    
    /**
     * Get table actions with memory consideration
     */
    protected function getMemoryOptimizedTableActions(): array
    {
        return [
            Tables\Actions\ViewAction::make()
                ->label('Lihat'),
            Tables\Actions\EditAction::make()
                ->label('Edit'),
        ];
    }
    
    /**
     * Get bulk actions with memory limits
     */
    protected function getMemoryOptimizedBulkActions(): array
    {
        $maxBulkActions = min(100, FilamentMemoryOptimizationService::getOptimalChunkSize());
        
        return [
            Tables\Actions\DeleteBulkAction::make()
                ->label('Hapus')
                ->requiresConfirmation()
                ->modalHeading('Hapus Data Terpilih')
                ->modalDescription("Anda akan menghapus data terpilih. Maksimal {$maxBulkActions} item per operasi.")
                ->modalSubmitActionLabel('Ya, Hapus'),
        ];
    }
    
    /**
     * Get table filters optimized for memory
     */
    protected function getMemoryOptimizedTableFilters(): array
    {
        return [
            Tables\Filters\Filter::make('created_at')
                ->form([
                    Forms\Components\DatePicker::make('created_from')
                        ->label('Dari Tanggal'),
                    Forms\Components\DatePicker::make('created_until')
                        ->label('Sampai Tanggal'),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return $query
                        ->when(
                            $data['created_from'],
                            fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                        )
                        ->when(
                            $data['created_until'],
                            fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                        );
                }),
        ];
    }
    
    /**
     * Clear memory before heavy operations
     */
    protected function clearMemoryBeforeOperation(): void
    {
        if (FilamentMemoryOptimizationService::isMemoryLimitApproaching(0.7)) {
            FilamentMemoryOptimizationService::clearMemory();
        }
    }
    
    /**
     * Get header actions with memory optimization
     */
    protected function getMemoryOptimizedHeaderActions(): array
    {
        return [
            Tables\Actions\CreateAction::make()
                ->label('Tambah Baru')
                ->mutateFormDataUsing(function (array $data): array {
                    $this->clearMemoryBeforeOperation();
                    return $data;
                }),
        ];
    }
    
    /**
     * Process large datasets safely
     */
    protected function processLargeDataset(Builder $query, callable $callback): void
    {
        FilamentMemoryOptimizationService::processInChunks($query, $callback);
    }
    
    /**
     * Get export action with memory limits
     */
    protected function getMemoryOptimizedExportAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('export')
            ->label('Export')
            ->icon('heroicon-o-arrow-down-tray')
            ->action(function () {
                $this->clearMemoryBeforeOperation();
                
                // Limit export to prevent memory issues
                $maxExportRecords = FilamentMemoryOptimizationService::getOptimalPageSize() * 10;
                
                $this->notify(
                    'warning',
                    "Export dibatasi maksimal {$maxExportRecords} records untuk mencegah memory overflow."
                );
                
                // Implement your export logic here
            });
    }
    
    /**
     * Get memory status for debugging
     */
    protected function getMemoryStatus(): string
    {
        $usage = FilamentMemoryOptimizationService::getMemoryUsage();
        return "Memory: {$usage['current_usage_formatted']} / {$usage['limit']} ({$usage['usage_percentage']}%)";
    }
}
