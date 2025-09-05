<?php

namespace App\Filament\Resources;

use App\Services\FilamentMemoryOptimizationService;
use App\Traits\FilamentMemoryOptimizedTrait;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

abstract class BaseMemoryOptimizedResource extends Resource
{
    use FilamentMemoryOptimizedTrait;
    
    /**
     * Configure table with memory optimization by default
     */
    public static function table(Table $table): Table
    {
        $instance = new static();
        
        return $instance->configureMemoryOptimizedTable($table)
            ->columns($instance->getTableColumns())
            ->filters($instance->getTableFilters())
            ->actions($instance->getTableActions())
            ->bulkActions($instance->getBulkTableActions())
            ->headerActions($instance->getHeaderActions())
            ->modifyQueryUsing(fn (Builder $query) => $instance->applyMemoryOptimization($query));
    }
    
    /**
     * Get table columns - override in child classes
     */
    protected function getTableColumns(): array
    {
        return $this->getOptimizedTableColumns();
    }
    
    /**
     * Get table filters - override in child classes
     */
    protected function getTableFilters(): array
    {
        return $this->getMemoryOptimizedTableFilters();
    }
    
    /**
     * Get table actions - override in child classes
     */
    protected function getTableActions(): array
    {
        return $this->getMemoryOptimizedTableActions();
    }
    
    /**
     * Get bulk table actions - override in child classes
     */
    protected function getBulkTableActions(): array
    {
        return $this->getMemoryOptimizedBulkActions();
    }
    
    /**
     * Get header actions - override in child classes
     */
    protected function getHeaderActions(): array
    {
        return [];
    }
    
    /**
     * Get pages with memory optimization notices
     */
    public static function getPages(): array
    {
        return [
            'index' => static::getIndexPage(),
            'create' => static::getCreatePage(),
            'view' => static::getViewPage(),
            'edit' => static::getEditPage(),
        ];
    }
    
    /**
     * Get index page class
     */
    protected static function getIndexPage(): string
    {
        return Pages\ListRecords::class;
    }
    
    /**
     * Get create page class
     */
    protected static function getCreatePage(): string
    {
        return Pages\CreateRecord::class;
    }
    
    /**
     * Get view page class
     */
    protected static function getViewPage(): string
    {
        return Pages\ViewRecord::class;
    }
    
    /**
     * Get edit page class
     */
    protected static function getEditPage(): string
    {
        return Pages\EditRecord::class;
    }
}

/**
 * Base Pages with Memory Optimization
 */
namespace App\Filament\Resources\Pages;

use App\Services\FilamentMemoryOptimizationService;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords as BaseListRecords;
use Filament\Resources\Pages\CreateRecord as BaseCreateRecord;
use Filament\Resources\Pages\ViewRecord as BaseViewRecord;
use Filament\Resources\Pages\EditRecord as BaseEditRecord;

class ListRecords extends BaseListRecords
{
    protected function getHeaderActions(): array
    {
        $memoryStatus = FilamentMemoryOptimizationService::getMemoryUsage();
        
        $actions = [
            Actions\CreateAction::make()
                ->label('Tambah Baru'),
        ];
        
        // Show memory warning if needed
        if (FilamentMemoryOptimizationService::isMemoryLimitApproaching(0.8)) {
            $actions[] = Actions\Action::make('memory_warning')
                ->label('⚠️ Memory Tinggi')
                ->color('warning')
                ->action(function () {
                    FilamentMemoryOptimizationService::clearMemory();
                    $this->redirect($this->getResource()::getUrl('index'));
                })
                ->tooltip("Memory usage: {$memoryStatus['usage_percentage']}%");
        }
        
        return $actions;
    }
    
    public function mount(): void
    {
        parent::mount();
        
        // Clear memory on page load if needed
        if (FilamentMemoryOptimizationService::isMemoryLimitApproaching(0.7)) {
            FilamentMemoryOptimizationService::clearMemory();
        }
    }
}

class CreateRecord extends BaseCreateRecord
{
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Clear memory before heavy operations
        if (FilamentMemoryOptimizationService::isMemoryLimitApproaching(0.7)) {
            FilamentMemoryOptimizationService::clearMemory();
        }
        
        return parent::mutateFormDataBeforeCreate($data);
    }
}

class ViewRecord extends BaseViewRecord
{
    public function mount(int | string $record): void
    {
        // Clear memory before loading record with relations
        if (FilamentMemoryOptimizationService::isMemoryLimitApproaching(0.7)) {
            FilamentMemoryOptimizationService::clearMemory();
        }
        
        parent::mount($record);
    }
}

class EditRecord extends BaseEditRecord
{
    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Clear memory before loading form data
        if (FilamentMemoryOptimizationService::isMemoryLimitApproaching(0.7)) {
            FilamentMemoryOptimizationService::clearMemory();
        }
        
        return parent::mutateFormDataBeforeFill($data);
    }
    
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Clear memory before saving
        if (FilamentMemoryOptimizationService::isMemoryLimitApproaching(0.7)) {
            FilamentMemoryOptimizationService::clearMemory();
        }
        
        return parent::mutateFormDataBeforeSave($data);
    }
}
