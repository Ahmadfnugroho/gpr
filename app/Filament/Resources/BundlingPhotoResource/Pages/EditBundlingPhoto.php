<?php

namespace App\Filament\Resources\BundlingPhotoResource\Pages;

use App\Filament\Resources\BundlingPhotoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBundlingPhoto extends EditRecord
{
    protected static string $resource = BundlingPhotoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
