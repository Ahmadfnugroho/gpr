<?php

namespace App\Filament\Resources\ProductAvailabilityResource\Pages;

use App\Filament\Resources\ProductAvailabilityResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\ActionSize;

class ListProductAvailabilities extends ListRecords
{
    protected static string $resource = ProductAvailabilityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('refresh')
                ->label('Refresh Data')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->size(ActionSize::Small)
                ->action(function () {
                    $this->dispatch('$refresh');
                    
                    \Filament\Notifications\Notification::make()
                        ->title('Data refreshed')
                        ->body('Product availability data has been updated.')
                        ->success()
                        ->send();
                }),
            
            Actions\Action::make('help')
                ->label('Help')
                ->icon('heroicon-o-question-mark-circle')
                ->color('gray')
                ->size(ActionSize::Small)
                ->modalHeading('Product Availability Help')
                ->modalContent(view('filament.pages.product-availability-help'))
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Close'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            // Can add widgets here for summary statistics if needed
        ];
    }

    public function getTitle(): string
    {
        return 'Product Availability';
    }

    public function getSubheading(): ?string
    {
        return 'Real-time product availability tracking with date range filtering. Data updates every 30 seconds.';
    }
}
