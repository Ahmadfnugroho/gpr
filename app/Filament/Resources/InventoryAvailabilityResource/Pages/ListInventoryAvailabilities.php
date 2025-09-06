<?php

namespace App\Filament\Resources\InventoryAvailabilityResource\Pages;

use App\Filament\Resources\InventoryAvailabilityResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInventoryAvailabilities extends ListRecords
{
    protected static string $resource = InventoryAvailabilityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action since this is read-only
        ];
    }
}
