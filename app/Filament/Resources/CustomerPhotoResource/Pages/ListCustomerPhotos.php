<?php

namespace App\Filament\Resources\CustomerPhotoResource\Pages;

use App\Filament\Resources\CustomerPhotoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCustomerPhotos extends ListRecords
{
    protected static string $resource = CustomerPhotoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
