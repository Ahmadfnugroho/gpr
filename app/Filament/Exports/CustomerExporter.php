<?php

namespace App\Filament\Exports;

use App\Models\Customer;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class CustomerExporter extends Exporter
{
    protected static ?string $model = Customer::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('name')
                ->label('Nama Lengkap'),

            ExportColumn::make('email')
                ->label('Email'),

            ExportColumn::make('phone_number_1')
                ->label('Nomor HP 1')
                ->formatStateUsing(function (Customer $record): string {
                    return $record->customerPhoneNumbers->first()?->phone_number ?? '';
                }),

            ExportColumn::make('phone_number_2')
                ->label('Nomor HP 2')
                ->formatStateUsing(function (Customer $record): string {
                    return $record->customerPhoneNumbers->skip(1)->first()?->phone_number ?? '';
                }),

            ExportColumn::make('gender')
                ->label('Jenis Kelamin')
                ->formatStateUsing(fn (Customer $record): string => match($record->gender) {
                    'male' => 'Laki-laki',
                    'female' => 'Perempuan',
                    default => $record->gender ?? ''
                }),

            ExportColumn::make('status')
                ->label('Status')
                ->formatStateUsing(fn (Customer $record): string => Customer::STATUS_LABELS[$record->status] ?? $record->status ?? ''),

            ExportColumn::make('address')
                ->label('Alamat'),

            ExportColumn::make('job')
                ->label('Pekerjaan'),

            ExportColumn::make('office_address')
                ->label('Alamat Kantor'),

            ExportColumn::make('instagram_username')
                ->label('Instagram'),

            ExportColumn::make('emergency_contact_name')
                ->label('Kontak Emergency'),

            ExportColumn::make('emergency_contact_number')
                ->label('HP Emergency'),

            ExportColumn::make('source_info')
                ->label('Sumber Info'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your customer export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
