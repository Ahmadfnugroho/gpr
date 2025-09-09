<?php

namespace App\Filament\Resources\PromoResource\Pages;

use App\Filament\Resources\PromoResource;
use App\Filament\Concerns\HasSuccessNotification;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPromo extends EditRecord
{
    use HasSuccessNotification;
    
    protected static string $resource = PromoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    /**
     * Mutate form data before saving the record
     * Ensure rules is always an array for JSON storage
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Log the data for debugging (can remove after testing)
        \Illuminate\Support\Facades\Log::info('EditPromo - mutateFormDataBeforeSave', [
            'rules_data' => $data['rules'] ?? null,
            'rules_type' => gettype($data['rules'] ?? null),
        ]);

        // Ensure rules is always an array
        if (isset($data['rules'])) {
            if (is_string($data['rules'])) {
                $data['rules'] = json_decode($data['rules'], true) ?: [];
            } elseif (is_null($data['rules'])) {
                $data['rules'] = [];
            }
        } else {
            $data['rules'] = [];
        }

        return $data;
    }
}
