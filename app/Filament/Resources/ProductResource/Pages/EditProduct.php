<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Filament\Concerns\HasSuccessNotification;
use App\Models\ProductPhoto;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

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
                    ->title('Additional Photos Uploaded')
                    ->body("Successfully uploaded {$uploadedCount} additional product photo(s) to the gallery.")
                    ->success()
                    ->send();
            }
        }
    }
    
    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Load existing photos for display (optional)
        $data['existing_photos'] = $this->record->productPhotos->pluck('photo')->toArray();
        
        return $data;
    }
}
