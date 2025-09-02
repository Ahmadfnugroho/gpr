<?php

namespace App\Filament\Resources\CustomerPhotoResource\Pages;

use App\Filament\Resources\CustomerPhotoResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomerPhoto extends CreateRecord
{
    protected static string $resource = CustomerPhotoResource::class;
}
