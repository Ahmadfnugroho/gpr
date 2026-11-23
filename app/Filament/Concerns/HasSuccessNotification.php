<?php

namespace App\Filament\Concerns;

use Filament\Notifications\Notification;

trait HasSuccessNotification
{
    /**
     * Redirect to index page after successful edit
     */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * Handle successful save with notification
     * This method is called automatically by Filament after a successful save
     */
    protected function getSavedNotification(): ?Notification
    {
        $entityName = str_replace('Resource', '', class_basename($this->getResource()));

        return Notification::make()
            ->success()
            ->title($entityName . ' berhasil disimpan!')
            ->body('Data ' . strtolower($entityName) . ' telah berhasil diperbarui.');
    }
}
