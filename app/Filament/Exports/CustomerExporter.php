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
            ExportColumn::make('nama_lengkap')
                ->label('Nama Lengkap')
                ->formatStateUsing(fn (Customer $record): string => $record->name ?? ''),

            ExportColumn::make('email')
                ->label('Email'),

            ExportColumn::make('nomor_hp_1')
                ->label('Nomor HP 1')
                ->formatStateUsing(function (Customer $record): string {
                    return $record->customerPhoneNumbers->first()?->phone_number ?? '';
                }),

            ExportColumn::make('nomor_hp_2')
                ->label('Nomor HP 2')
                ->formatStateUsing(function (Customer $record): string {
                    return $record->customerPhoneNumbers->skip(1)->first()?->phone_number ?? '';
                }),

            ExportColumn::make('jenis_kelamin')
                ->label('Jenis Kelamin')
                ->formatStateUsing(fn (Customer $record): string => match($record->gender) {
                    'male' => 'Laki-laki',
                    'female' => 'Perempuan',
                    default => $record->gender ?? ''
                }),

            ExportColumn::make('status')
                ->label('Status')
                ->formatStateUsing(fn (Customer $record): string => Customer::STATUS_LABELS[$record->status] ?? $record->status ?? ''),

            ExportColumn::make('alamat')
                ->label('Alamat')
                ->formatStateUsing(fn (Customer $record): string => $record->address ?? ''),

            ExportColumn::make('pekerjaan')
                ->label('Pekerjaan')
                ->formatStateUsing(fn (Customer $record): string => $record->job ?? ''),

            ExportColumn::make('alamat_kantor')
                ->label('Alamat Kantor')
                ->formatStateUsing(fn (Customer $record): string => $record->office_address ?? ''),

            ExportColumn::make('instagram')
                ->label('Instagram')
                ->formatStateUsing(fn (Customer $record): string => $record->instagram_username ?? ''),

            ExportColumn::make('kontak_emergency')
                ->label('Kontak Emergency')
                ->formatStateUsing(fn (Customer $record): string => $record->emergency_contact_name ?? ''),

            ExportColumn::make('hp_emergency')
                ->label('HP Emergency')
                ->formatStateUsing(fn (Customer $record): string => $record->emergency_contact_number ?? ''),

            ExportColumn::make('sumber_info')
                ->label('Sumber Info')
                ->formatStateUsing(fn (Customer $record): string => $record->source_info ?? ''),
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
