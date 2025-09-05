<?php

namespace App\Filament\Imports;

use App\Models\Customer;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class CustomerImporter extends Importer
{
    protected static ?string $model = Customer::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('nama_lengkap')
                ->label('Nama Lengkap')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255']),

            ImportColumn::make('email')
                ->label('Email')
                ->rules(['nullable', 'email', 'max:255']),

            ImportColumn::make('nomor_hp_1')
                ->label('Nomor HP 1')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:20']),

            ImportColumn::make('nomor_hp_2')
                ->label('Nomor HP 2')
                ->rules(['nullable', 'string', 'max:20']),

            ImportColumn::make('jenis_kelamin')
                ->label('Jenis Kelamin')
                ->rules(['nullable', 'in:male,female,laki-laki,perempuan,l,p'])
                ->example('male'),

            ImportColumn::make('status')
                ->label('Status')
                ->rules(['nullable', 'in:active,inactive,blacklist'])
                ->example('active'),

            ImportColumn::make('alamat')
                ->label('Alamat')
                ->rules(['nullable', 'string']),

            ImportColumn::make('pekerjaan')
                ->label('Pekerjaan')
                ->rules(['nullable', 'string', 'max:255']),

            ImportColumn::make('alamat_kantor')
                ->label('Alamat Kantor')
                ->rules(['nullable', 'string']),

            ImportColumn::make('instagram')
                ->label('Instagram')
                ->rules(['nullable', 'string', 'max:255']),

            ImportColumn::make('kontak_emergency')
                ->label('Kontak Emergency')
                ->rules(['nullable', 'string', 'max:255']),

            ImportColumn::make('hp_emergency')
                ->label('HP Emergency')
                ->rules(['nullable', 'string', 'max:20']),

            ImportColumn::make('sumber_info')
                ->label('Sumber Info')
                ->rules(['nullable', 'string', 'max:255']),
        ];
    }

    public function resolveRecord(): ?Customer
    {
        // Check if customer exists by email or primary phone number
        $existingCustomer = null;
        
        if (!empty($this->data['email'])) {
            $existingCustomer = Customer::where('email', $this->data['email'])->first();
        }
        
        if (!$existingCustomer && !empty($this->data['nomor_hp_1'])) {
            $existingCustomer = Customer::whereHas('customerPhoneNumbers', function($query) {
                $query->where('phone_number', $this->data['nomor_hp_1']);
            })->first();
        }

        if ($existingCustomer && !($this->options['updateExisting'] ?? false)) {
            $this->addError('nama_lengkap', 'Customer already exists with this email or phone number. Enable update mode to modify existing customers.');
            return null;
        }

        return $existingCustomer ?? new Customer();
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your customer import has completed and ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }

    protected function beforeSave(): void
    {
        // Map imported columns to database fields
        $this->record->name = $this->data['nama_lengkap'];
        $this->record->email = $this->data['email'] ?? null;
        $this->record->address = $this->data['alamat'] ?? null;
        $this->record->job = $this->data['pekerjaan'] ?? null;
        $this->record->office_address = $this->data['alamat_kantor'] ?? null;
        $this->record->instagram_username = $this->data['instagram'] ?? null;
        $this->record->emergency_contact_name = $this->data['kontak_emergency'] ?? null;
        $this->record->emergency_contact_number = $this->data['hp_emergency'] ?? null;
        $this->record->source_info = $this->data['sumber_info'] ?? null;
        
        // Convert gender if needed
        if (!empty($this->data['jenis_kelamin'])) {
            $gender = strtolower(trim($this->data['jenis_kelamin']));
            if (in_array($gender, ['l', 'laki-laki', 'male', 'pria'])) {
                $this->record->gender = 'male';
            } elseif (in_array($gender, ['p', 'perempuan', 'female', 'wanita'])) {
                $this->record->gender = 'female';
            }
        }
        
        // Set status
        if (!empty($this->data['status'])) {
            $this->record->status = $this->data['status'];
        } else {
            $this->record->status = Customer::STATUS_ACTIVE;
        }
    }

    protected function afterSave(): void
    {
        // Handle phone numbers
        $this->handlePhoneNumbers();
    }

    private function handlePhoneNumbers(): void
    {
        // Delete existing phone numbers if updating
        if ($this->record->wasRecentlyCreated === false) {
            $this->record->customerPhoneNumbers()->delete();
        }

        // Add primary phone number
        if (!empty($this->data['nomor_hp_1'])) {
            $this->record->customerPhoneNumbers()->create([
                'phone_number' => $this->data['nomor_hp_1']
            ]);
        }

        // Add secondary phone number if provided
        if (!empty($this->data['nomor_hp_2'])) {
            $this->record->customerPhoneNumbers()->create([
                'phone_number' => $this->data['nomor_hp_2']
            ]);
        }
    }
}
