<?php

namespace App\Filament\Imports;

use App\Models\Brand;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Filament\Notifications\Notification;

class BrandImporter extends Importer
{
    protected static ?string $model = Brand::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('name')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('logo')
                ->rules(['max:255']),
        ];
    }

    public function resolveRecord(): ?Brand
    {
        $brand = new Brand();
        $brand->name = $this->data['name'];  // Mengisi field 'name' dengan data CSV
        $brand->logo = $this->data['logo'] ?? null;  // Mengisi field 'logo' dengan data CSV

        // Simpan ke database
        $brand->save();

        return $brand;
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your brand import has completed and ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        Notification::make()
            ->title('Import Completed')
            ->body($body)
            ->success();

        return $body;
    }
}
