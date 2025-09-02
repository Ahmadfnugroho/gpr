<?php

namespace App\Filament\Resources\CustomerPhotoResource\Pages;

use App\Filament\Resources\CustomerPhotoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCustomerPhoto extends EditRecord
{
    protected static string $resource = CustomerPhotoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
