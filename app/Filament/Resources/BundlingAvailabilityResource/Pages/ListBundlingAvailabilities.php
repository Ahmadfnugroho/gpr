<?php

namespace App\Filament\Resources\BundlingAvailabilityResource\Pages;

use App\Filament\Resources\BundlingAvailabilityResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBundlingAvailabilities extends ListRecords
{
    protected static string $resource = BundlingAvailabilityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No header actions needed for read-only resource
        ];
    }
}
