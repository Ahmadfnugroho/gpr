<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use App\Filament\Traits\RedirectToViewTrait;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomer extends CreateRecord
{
    use RedirectToViewTrait;
    
    protected static string $resource = CustomerResource::class;
}
