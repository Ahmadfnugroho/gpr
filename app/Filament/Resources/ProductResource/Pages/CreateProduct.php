<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Models\ProductPhoto;
use App\Services\ImageCompressionService;
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
            $compressionService = new ImageCompressionService();
            $uploadedCount = 0;
            $compressedCount = 0;
            
            foreach ($productPhotos as $photoPath) {
                // Check if it's already a stored file path or needs compression
                if (is_string($photoPath)) {
                    ProductPhoto::create([
                        'product_id' => $this->record->id,
                        'photo' => $photoPath,
                    ]);
                    $uploadedCount++;
                    
                    // Check if file was compressed (size under 2MB indicates compression)
                    $fullPath = storage_path('app/public/' . $photoPath);
                    if (file_exists($fullPath) && filesize($fullPath) < 2097152) {
                        $compressedCount++;
                    }
                }
            }
            
            if ($uploadedCount > 0) {
                $message = "Successfully uploaded {$uploadedCount} product photo(s) to the gallery.";
                if ($compressedCount > 0) {
                    $message .= " {$compressedCount} photo(s) were compressed to optimize file size.";
                }
                
                Notification::make()
                    ->title('Photos Uploaded')
                    ->body($message)
                    ->success()
                    ->send();
            }
        }
    }
}
