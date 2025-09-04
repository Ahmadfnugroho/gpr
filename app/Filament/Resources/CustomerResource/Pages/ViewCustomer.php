<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Grid;
use Filament\Support\Enums\FontWeight;

class ViewCustomer extends ViewRecord
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('whatsapp')
                ->label('WhatsApp')
                ->icon('heroicon-o-chat-bubble-left-ellipsis')
                ->color('success')
                ->url(function () {
                    $phone = $this->record->customerPhoneNumbers->first()?->phone_number;
                    if ($phone) {
                        $cleanPhone = preg_replace('/\D/', '', $phone);
                        if (str_starts_with($cleanPhone, '0')) {
                            $cleanPhone = '62' . substr($cleanPhone, 1);
                        } elseif (!str_starts_with($cleanPhone, '62')) {
                            $cleanPhone = '62' . $cleanPhone;
                        }
                        return "https://wa.me/{$cleanPhone}";
                    }
                    return null;
                })
                ->openUrlInNewTab()
                ->visible(fn() => $this->record->customerPhoneNumbers->isNotEmpty()),

            Actions\EditAction::make()
                ->successRedirectUrl(fn() => static::getResource()::getUrl('view', ['record' => $this->record])),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Informasi Dasar')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('name')
                                    ->label('Nama Lengkap')
                                    ->weight(FontWeight::SemiBold)
                                    ->size('lg'),

                                TextEntry::make('email')
                                    ->label('Email')
                                    ->copyable()
                                    ->copyMessage('Email berhasil disalin!')
                                    ->icon('heroicon-m-envelope'),

                                TextEntry::make('status')
                                    ->label('Status')
                                    ->badge()
                                    ->color(fn(string $state): string => match ($state) {
                                        'active' => 'success',
                                        'inactive' => 'warning',
                                        'blacklist' => 'danger',
                                        default => 'gray',
                                    })
                                    ->formatStateUsing(fn(string $state): string => match ($state) {
                                        'active' => 'Aktif',
                                        'inactive' => 'Tidak Aktif',
                                        'blacklist' => 'Blacklist',
                                        default => ucfirst($state)
                                    }),
                            ]),

                        Grid::make(3)
                            ->schema([
                                TextEntry::make('gender')
                                    ->label('Jenis Kelamin')
                                    ->formatStateUsing(fn(string $state): string => match ($state) {
                                        'male' => 'Laki-laki',
                                        'female' => 'Perempuan',
                                        default => $state
                                    })
                                    ->icon(fn(string $state): string => match ($state) {
                                        'male' => 'heroicon-m-user',
                                        'female' => 'heroicon-m-user',
                                        default => 'heroicon-m-user'
                                    }),

                                TextEntry::make('source_info')
                                    ->label('Sumber Info')
                                    ->badge()
                                    ->color('info'),

                                TextEntry::make('created_at')
                                    ->label('Terdaftar')
                                    ->dateTime('d M Y, H:i')
                                    ->since(),
                            ]),
                    ])->columns(1),

                Section::make('Kontak & Alamat')
                    ->schema([
                        TextEntry::make('address')
                            ->label('Alamat Lengkap')
                            ->placeholder('Tidak ada alamat')
                            ->columnSpanFull(),

                        Grid::make(2)
                            ->schema([
                                TextEntry::make('job')
                                    ->label('Pekerjaan')
                                    ->placeholder('Tidak diisi')
                                    ->icon('heroicon-m-briefcase'),

                                TextEntry::make('office_address')
                                    ->label('Alamat Kantor')
                                    ->placeholder('Tidak diisi'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextEntry::make('emergency_contact_name')
                                    ->label('Kontak Emergency')
                                    ->placeholder('Tidak diisi')
                                    ->icon('heroicon-m-user-plus'),

                                TextEntry::make('emergency_contact_number')
                                    ->label('No. HP Emergency')
                                    ->placeholder('Tidak diisi')
                                    ->copyable()
                                    ->url(fn($record) => $record->emergency_contact_number ? "tel:{$record->emergency_contact_number}" : null)
                                    ->icon('heroicon-m-phone'),
                            ]),
                    ])->columns(1),

                Section::make('Media Sosial')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('instagram_username')
                                    ->label('Instagram')
                                    ->placeholder('Tidak diisi')
                                    ->prefix('@')
                                    ->url(fn($record) => $record->instagram_username ? "https://instagram.com/{$record->instagram_username}" : null)
                                    ->openUrlInNewTab()
                                    ->color('primary'),

                            ]),
                    ])
                    ->columns(1)
                    ->collapsible(),

                Section::make('Statistik')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('customerPhotos')
                                    ->label('Total Foto')
                                    ->formatStateUsing(fn($record) => $record->customerPhotos->count() . ' foto')
                                    ->icon('heroicon-m-camera')
                                    ->color('info'),

                                TextEntry::make('customerPhoneNumbers')
                                    ->label('Total Nomor HP')
                                    ->formatStateUsing(fn($record) => $record->customerPhoneNumbers->count() . ' nomor')
                                    ->icon('heroicon-m-phone')
                                    ->color('success'),

                                TextEntry::make('transactions')
                                    ->label('Total Transaksi')
                                    ->formatStateUsing(fn($record) => $record->transactions->count() . ' transaksi')
                                    ->icon('heroicon-m-shopping-bag')
                                    ->color('warning'),

                                TextEntry::make('email_verified_at')
                                    ->label('Email Verified')
                                    ->formatStateUsing(fn($record) => $record->email_verified_at ? 'Terverifikasi' : 'Belum Verifikasi')
                                    ->badge()
                                    ->color(fn($record) => $record->email_verified_at ? 'success' : 'danger')
                                    ->icon(fn($record) => $record->email_verified_at ? 'heroicon-m-check-badge' : 'heroicon-m-x-circle'),
                            ]),
                    ])->columns(1),
            ]);
    }
}
