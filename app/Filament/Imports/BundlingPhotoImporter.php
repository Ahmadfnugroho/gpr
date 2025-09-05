<?php

namespace App\Filament\Imports;

use App\Models\Bundling;
use App\Models\BundlingPhoto;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class BundlingPhotoImporter extends Importer
{
    protected static ?string $model = BundlingPhoto::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('bundling_id')
                ->label('Bundling Id')
                ->requiredMapping()
                ->rules(['required']),
            ImportColumn::make('photo')
                ->label('Photo')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255']),
        ];
    }

    public function resolveRecord(): ?BundlingPhoto
    {
        $bundling = Bundling::where('id', $this->data['bundling_id'])->first();

        if (!$bundling) {
            return null;
        }

        return BundlingPhoto::firstOrNew([
            'bundling_id' => $bundling->id,
            'photo' => $this->data['photo'],
        ]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your bundling photo import has completed and ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
