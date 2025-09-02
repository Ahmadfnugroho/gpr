<?php

namespace App\Filament\Resources\CustomerPhoneNumberResource\Pages;

use App\Filament\Resources\CustomerPhoneNumberResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCustomerPhoneNumbers extends ListRecords
{
    protected static string $resource = CustomerPhoneNumberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
