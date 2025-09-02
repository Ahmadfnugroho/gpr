<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use App\Filament\Traits\RedirectToViewTrait;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCustomer extends EditRecord
{
    use RedirectToViewTrait;
    
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
