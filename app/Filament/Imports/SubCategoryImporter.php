<?php

namespace App\Filament\Imports;

use App\Models\SubCategory;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class SubCategoryImporter extends Importer
{
    protected static ?string $model = SubCategory::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('name')
                ->rules(['max:255']),
            ImportColumn::make('photo')
                ->rules(['max:255']),
        ];
    }

    public function resolveRecord(): ?SubCategory
    {
        $subCategory = new SubCategory();
        $subCategory->name = $this->data['name'];  // Mengisi field 'name' dengan data CSV
        $subCategory->photo = $this->data['photo'] ?? null;  // Mengisi field 'photo' dengan data CSV

        // Simpan ke database
        $subCategory->save();

        return $subCategory;
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your sub category import has completed and ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
