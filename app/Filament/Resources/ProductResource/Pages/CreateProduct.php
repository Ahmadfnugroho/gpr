<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Models\ProductPhoto;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;
    
    protected function afterCreate(): void
    {
        $this->handleProductPhotoUploads();
    }
    
    protected function handleProductPhotoUploads(): void
    {
        $productPhotos = data_get($this->data, 'product_photos', []);
        
        if (!empty($productPhotos) && is_array($productPhotos)) {
            $uploadedCount = 0;
            
            foreach ($productPhotos as $photo) {
                ProductPhoto::create([
                    'product_id' => $this->record->id,
                    'photo' => $photo,
                ]);
                $uploadedCount++;
            }
            
            if ($uploadedCount > 0) {
                Notification::make()
                    ->title('Photos Uploaded')
                    ->body("Successfully uploaded {$uploadedCount} product photo(s) to the gallery.")
                    ->success()
                    ->send();
            }
        }
    }
}
