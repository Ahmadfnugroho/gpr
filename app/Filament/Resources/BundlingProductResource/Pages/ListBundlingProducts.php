<?php

namespace App\Filament\Resources\BundlingProductResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBundlingProducts extends ListRecords
{

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
