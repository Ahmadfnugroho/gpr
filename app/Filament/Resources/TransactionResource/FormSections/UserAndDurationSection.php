<?php

namespace App\Filament\Resources\TransactionResource\FormSections;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\DateTimePicker;
use Carbon\Carbon;
use App\Models\User;
use App\Models\UserPhoneNumber;
use Filament\Forms\Components\Grid;
use Filament\Forms\Get;

class UserAndDurationSection
{
    public static function getSchema(): array
    {
        return [
            Section::make('Data Penyewa dan Tanggal')
                ->schema([
                    Grid::make('User')
                        ->schema([
                            Select::make('user_id')
                                ->relationship('user', 'name')
                                ->required()
                                ->searchable()
                                ->preload()
                                ->live()
                                ->columnSpan(1)
                                ->afterStateUpdated(function ($state, callable $set) {
                                    $set('user_id', $state);
                                    $user = \App\Models\User::find($state);
                                    $set('user_status', $user ? $user->status : null);
                                    $set('user_email', $user ? $user->email : null);
                                    $phoneNumber = \App\Models\UserPhoneNumber::where('user_id', $state)->first();
                                    $set('user_phone_number', $phoneNumber ? $phoneNumber->phone_number : null);
                                }),

                            Placeholder::make('user_status')
                                ->label('Status')
                                ->content(function (Get $get) {

                                    $user = User::find($get('user_id'));

                                    return (string) ($user ? $user->status : '-');
                                }),
                            Placeholder::make('user_email')
                                ->label('Email')
                                ->columnSpan('auto')
                                ->content(function (Get $get) {
                                    // Jika tidak ada customId (product belum dipilih), ambil dari transaksi sebelumnya
                                    $user = User::find($get('user_id'));

                                    return (string) ($user ? $user->email : '-');
                                }),
                            Placeholder::make('user_phone_number')
                                ->label('No Telepon')
                                ->content(function (Get $get) {

                                    $phoneNumbers = UserPhoneNumber::where('user_id', $get('user_id'))->pluck('phone_number')->toArray();

                                    return !empty($phoneNumbers) ? implode(', ', $phoneNumbers) : '-';
                                }),
                        ])
                        ->columnSpan(1)
                        ->columns(2),

                    Grid::make('Durasi')
                        ->schema([
                            DateTimePicker::make('start_date')
                                ->label('Start Date')
                                ->seconds(false)
                                ->native(false)
                                ->displayFormat('d M Y, H:i')

                                ->format('d M Y, H:i')
                                ->required()
                                ->reactive()
                                ->default(now())
                                ->minDate(now()->subWeek())
                                ->afterStateUpdated(function ($state, $get, $set) {
                                    $record = $get('record');
                                    $startDate = Carbon::parse($state)->format('Y-m-d H:i:s');
                                    $duration = (int) $get('duration');

                                    if ($startDate && $duration) {
                                        $endDate = Carbon::parse($startDate)->addDays($duration - 1)->endOfDay()->format('Y-m-d H:i');

                                        $set('end_date', $endDate);
                                    }
                                }),
                            Select::make('duration')
                                ->label('Duration')
                                ->required()
                                ->default(1)
                                ->options(array_combine(range(1, 30), range(1, 30)))
                                ->searchable()
                                ->suffix('Hari')
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $set, callable $get) {

                                    $startDate = $get('start_date');
                                    $duration = (int) $state;
                                    if ($startDate && $duration) {
                                        $endDate = Carbon::parse($startDate)->addDays($duration - 1)->endOfDay()->format('Y-m-d H:i:s');

                                        $set('end_date', $endDate);
                                    }
                                }),
                            Placeholder::make('end_date')
                                ->label('End Date')
                                ->reactive()

                                ->content(function ($get, $set) {

                                    $startDate = Carbon::parse($get('start_date'));
                                    $duration = (int) $get('duration');


                                    if ($startDate && $duration) {
                                        $endDate = Carbon::parse($startDate->addDays($duration)->format('d M Y, H:i'));

                                        $set('end_date', $endDate);

                                        $detailTransactions = $get('DetailTransactions') ?? [];
                                        foreach ($detailTransactions as $detailTransaction) {

                                            $productId = $detailTransaction['product_id'] ?? null;
                                            $product = \App\Models\Product::find($productId);
                                            $customId = $product ? $product->custom_id : null;




                                            $productName = '';
                                            $availableQuantity = 0;
                                            $transactionId = $get('id'); // Get current transaction ID

                                            if (str_starts_with($customId, 'bundling-')) {
                                                $bundlingId = (int) substr($customId, 9);
                                                $bundlingProducts = \App\Models\Bundling::find($bundlingId)?->products;
                                                $availableQuantity = 0;
                                                foreach ($bundlingProducts as $bundlingProduct) {
                                                    $productName = $bundlingProduct->name;
                                                    $includedStatuses = ['rented', 'paid', 'pending'];
                                                    $rentedQuantity = \App\Models\DetailTransaction::where('product_id', $bundlingProduct->id)
                                                        ->whereNotIn('id', [$transactionId])

                                                        ->whereHas('transaction', function ($query) use ($startDate, $endDate, $includedStatuses) {
                                                            $query->whereIn('booking_status', $includedStatuses)
                                                                ->where('start_date', '<=', $endDate)
                                                                ->where('end_date', '>=', $startDate);
                                                        })
                                                        ->sum('quantity');
                                                    $availableQuantity += $bundlingProduct->quantity - $rentedQuantity;
                                                }
                                                if ($availableQuantity <= 0) {
                                                    return "Produk {$bundlingProducts->first()->name} tidak tersedia pada rentang tanggal {$startDate->format('d M Y, H:i')} hingga {$endDate}.";
                                                }
                                            } elseif (str_starts_with($customId, 'produk-')) {
                                                $productId = (int) substr($customId, 7);
                                                $product = \App\Models\Product::find($productId);
                                                $productName = $product?->name;
                                                if ($product) {
                                                    $includedStatuses = ['rented', 'paid', 'pending'];
                                                    $rentedQuantity = \App\Models\DetailTransaction::where('product_id', $productId)

                                                        ->whereHas('transaction', function ($query) use ($startDate, $endDate, $includedStatuses, $transactionId) {
                                                            $query->whereIn('booking_status', $includedStatuses)
                                                                ->whereNotIn('id', [$transactionId])
                                                                ->where('start_date', '<=', $endDate)
                                                                ->where('end_date', '>=', $startDate);
                                                        })
                                                        ->sum('quantity');
                                                    $availableQuantity = $product->quantity - $rentedQuantity;
                                                    // log::info('rentedQuantity: ' . $rentedQuantity);
                                                    if ($availableQuantity <= 0) {

                                                        return "Produk {$productName} tidak tersedia pada rentang tanggal {$startDate->format('d M Y, H:i')} hingga {$endDate}.";
                                                    }
                                                } else {
                                                    return 'Produk belum diisi';
                                                }
                                            }
                                        }

                                        return $endDate->format('d M Y, H:i');
                                    }
                                    return 'start date atau durasi belum ditentukan.';
                                })
                                ->columnSpanFull(),
                        ])
                        ->columnSpan(1)
                        ->columns(2),

                ])

                ->columns(2),
        ];
    }
}
