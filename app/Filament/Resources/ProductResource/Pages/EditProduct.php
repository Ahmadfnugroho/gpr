<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Filament\Concerns\HasSuccessNotification;
use App\Models\ProductPhoto;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    use HasSuccessNotification;

    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        foreach (data_get($this->data, 'product_photos', []) as $photoPath) {
            ProductPhoto::create([
                'product_id' => $this->record->id,
                'photo' => $photoPath,
            ]);
        }
    }
}
