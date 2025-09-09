<?php

namespace App\Filament\Resources\PromoResource\Pages;

use App\Filament\Resources\PromoResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;

class CreatePromo extends CreateRecord
{
    protected static string $resource = PromoResource::class;
    
    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    /**
     * Mutate form data before creating the record
     * Ensure rules is always an array for JSON storage
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Log the data for debugging (can remove after testing)
        Log::info('CreatePromo - mutateFormDataBeforeCreate', [
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
