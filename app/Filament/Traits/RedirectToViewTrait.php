<?php

namespace App\Filament\Traits;

trait RedirectToViewTrait
{
    protected function getRedirectUrl(): string
    {
        // Check if we have a view route available
        $viewRoute = $this->getResource()::getUrl('view', ['record' => $this->record]);
        
        if ($viewRoute) {
            return $viewRoute;
        }
        
        // Fallback to index if no view route available
        return $this->getResource()::getUrl('index');
    }
    
    protected function getSavedNotificationTitle(): ?string
    {
        return 'Data berhasil disimpan!';
    }
}
