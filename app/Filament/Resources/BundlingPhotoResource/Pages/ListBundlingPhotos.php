<?php

namespace App\Filament\Resources\BundlingPhotoResource\Pages;

use App\Filament\Resources\BundlingPhotoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBundlingPhotos extends ListRecords
{
    protected static string $resource = BundlingPhotoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
