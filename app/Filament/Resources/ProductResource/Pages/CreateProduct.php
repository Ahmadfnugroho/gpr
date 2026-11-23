<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Models\ProductPhoto;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected function afterCreate(): void
    {
        foreach (data_get($this->data, 'product_photos', []) as $photoPath) {
            ProductPhoto::create([
                'product_id' => $this->record->id,
                'photo' => $photoPath,
            ]);
        }
    }
}
