<?php

namespace App\Filament\Resources\BundlingProductResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBundlingProduct extends EditRecord
{

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
