<?php

namespace App\Filament\Resources\TransactionResource\FormSections;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Forms\Set;
use App\Models\Promo;
use App\Helpers\Number;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Notifications\Notification;

class PaymentStatusSection
{
    public static function getSchema(): array
    {
        return [
            Section::make('Keterangan')
                ->schema([

                    Grid::make('Pembayaran')
                        ->schema([
                            Select::make('promo_id')
                                ->label('Input kode Promo')
                                ->relationship('promo', 'name')
                                ->searchable()
                                ->nullable()
                                ->preload()
                                ->live()
                                ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                ->columnSpanFull(),
                            Placeholder::make('total_before_discount')
                                ->label('Total Sebelum Diskon')
                                ->reactive()
                                ->content(function (Get $get) {
                                    $total = 0;
                                    $duration = (int) ($get('duration') ?? 1);

                                    $repeaters = $get('DetailTransactions');
                                    $record = $get('record');
                                    if (!$repeaters) {
                                        return Number::currency($total, 'IDR');
                                    }

                                    foreach ($repeaters as $key => $repeater) {
                                        $total += (int) $get('DetailTransactions.' . $key . '.total') +
                                            (int) $get('DetailTransactions.' . $key . '.total_price');
                                    }

                                    return Number::currency($total * $duration, 'IDR');
                                }),
                            Placeholder::make('discount_given')
                                ->label('Diskon Diberikan')
                                ->content(function (Get $get) {
                                    $total = 0;
                                    $promoId = $get('promo_id');
                                    $duration = (int) ($get('duration') ?? 1);
                                    $repeaters = $get('DetailTransactions');

                                    if (!$repeaters) {
                                        return Number::currency(0, 'IDR');
                                    }

                                    foreach ($repeaters as $key => $repeater) {
                                        $total += (int) $get('DetailTransactions.' . $key . '.total') +
                                            (int) $get('DetailTransactions.' . $key . '.total_price');
                                    }

                                    $promo = \App\Models\Promo::find($promoId);
                                    if (!$promo) {
                                        return Number::currency(0, 'IDR');
                                    }

                                    $rules = $promo->rules;
                                    $nominalDiscount = 0;

                                    if ($promo->type === 'day_based') {
                                        $groupSize = isset($rules[0]['group_size']) ? (int) $rules[0]['group_size'] : 1;
                                        $payDays = isset($rules[0]['pay_days']) ? (int) $rules[0]['pay_days'] : $groupSize;

                                        $discountedDays = (int) ($duration / $groupSize) * $payDays;
                                        $remainingDays = $duration % $groupSize;
                                        $daysToPay = $discountedDays + $remainingDays;

                                        $nominalDiscount = ($total * $duration) - ($total * $daysToPay);
                                    } elseif ($promo->type === 'percentage') {
                                        $percentage = isset($rules[0]['percentage']) ? (float) $rules[0]['percentage'] : 0;
                                        $nominalDiscount = ($total * $duration) * ($percentage / 100);
                                    } elseif ($promo->type === 'nominal') {
                                        $nominal = isset($rules[0]['nominal']) ? (float) $rules[0]['nominal'] : 0;
                                        $nominalDiscount = min($nominal, $total * $duration);
                                    }

                                    return Number::currency((int) $nominalDiscount, 'IDR');
                                }),
                            Placeholder::make('grand_total')
                                ->label('Grand Total')
                                ->content(function (Get $get, Set $set) {
                                    $total = 0;
                                    $promoId = $get('promo_id');
                                    $duration = (int) ($get('duration') ?? 1);
                                    $repeaters = $get('DetailTransactions') ?? [];

                                    foreach ($repeaters as $key => $repeater) {
                                        $total += (int) ($get('DetailTransactions.' . $key . '.total') ?? 0) +
                                            (int) ($get('DetailTransactions.' . $key . '.total_price') ?? 0);
                                    }

                                    $promo = \App\Models\Promo::find($promoId);
                                    if (!$promo) {
                                        $grandTotal = (int) $total * $duration;
                                        $set('grand_total', (int) $grandTotal);
                                        return Number::currency($grandTotal, 'IDR');
                                    }

                                    $rules = $promo->rules;
                                    $grandTotal = (int) $total * $duration;

                                    if ($promo->type === 'day_based') {
                                        $groupSize = $rules[0]['group_size'] ?? 1;
                                        $payDays = $rules[0]['pay_days'] ?? $groupSize;

                                        $discountedDays = (int) ($duration / $groupSize) * $payDays;
                                        $remainingDays = $duration % $groupSize;
                                        $daysToPay = $discountedDays + $remainingDays;

                                        $grandTotal = (int) $total * $daysToPay;
                                    } elseif ($promo->type === 'percentage') {
                                        $percentage = $rules[0]['percentage'] ?? 0;
                                        $grandTotal = ((int) $total * $duration) - (((int) $total * $duration) * ($percentage / 100));
                                    } elseif ($promo->type === 'nominal') {
                                        $nominal = $rules[0]['nominal'] ?? 0;
                                        $grandTotal = ((int) $total * $duration) - min($nominal, (int) $total * $duration);
                                    }


                                    $set('grand_total', (int) $grandTotal);
                                    $set('Jumlah_tagihan', intval($grandTotal));


                                    return Number::currency($grandTotal, 'IDR');
                                })
                                ->reactive(),

                            Hidden::make('grand_total')
                                ->default(fn(Get $get): string => (string) $get('grand_total')),
                            TextInput::make('down_payment')
                                ->label('Jumlah Pembayaran/DP')
                                ->required()
                                ->numeric()
                                ->reactive()
                                ->default(fn(Get $get): int => $get('grand_total') ? intval($get('grand_total') * 0.5) : 0)

                                ->minValue(fn(Get $get) => $get('grand_total') ? intval($get('grand_total') * 0.5) : 0)
                                ->maxValue(fn(Get $get) => $get('grand_total') ? intval($get('grand_total')) : 0),

                            // Placeholder::make('remaining_payment')
                            //     ->label('Pelunasan')
                            //     ->content(function (Get $get, Set $set) {
                            //         $remainingPayment = (int) $get('grand_total') - (int) $get('down_payment');
                            //         $set('remaining_payment', $remainingPayment);
                            //         return $remainingPayment === 0 ? 'LUNAS' : Number::currency($remainingPayment, 'IDR');
                            //     }),



                            Hidden::make('remaining_payment')
                                ->default(fn(Get $get): string => (string) $get('remaining_payment')),







                        ])
                        ->columnSpan(1)
                        ->columns(3),
                    Grid::make('Status dan Note')
                        ->schema([
                            ToggleButtons::make('booking_status')
                                ->options([
                                    'pending' => 'pending',
                                    'paid' => 'paid',
                                    'cancelled' => 'cancelled',
                                    'rented' => 'rented',
                                    'finished' => 'finished',
                                ])
                                ->icons([
                                    'pending' => 'heroicon-o-clock',
                                    'cancelled' => 'heroicon-o-x-circle',
                                    'rented' => 'heroicon-o-shopping-bag',
                                    'finished' => 'heroicon-o-check',
                                    'paid' => 'heroicon-o-banknotes',
                                ])
                                ->colors([
                                    'pending' => 'warning',
                                    'cancelled' => 'danger',
                                    'rented' => 'info',
                                    'finished' => 'success',
                                    'paid' => 'success',

                                ])
                                ->afterStateUpdated(fn(Set $set, Get $get, string $state) => match ($state) {
                                    'paid', 'rented', 'finished' => $set('down_payment', $get('grand_total')),
                                    default => null,
                                })
                                ->inline()
                                ->columnSpanFull()
                                ->grouped()
                                ->reactive()
                                ->default('pending')
                                ->helperText(function (Get $get) {
                                    $status = $get('booking_status');
                                    switch ($status) {
                                        case 'pending':
                                            return new \Illuminate\Support\HtmlString('Masih <strong style="color:red">DP</strong>  atau <strong style="color:red">belum pelunasan</strong> ');
                                        case 'paid':
                                            return new \Illuminate\Support\HtmlString('<strong style="color:green">Sewa sudah lunas</strong> tapi <strong style="color:red">barang belum diambil</strong>.');
                                        case 'rented':
                                            return new \Illuminate\Support\HtmlString('Sewa sudah  <strong style="color:blue">lunas </strong>dan barang sudah <strong style="color:blue">diambil</strong>');
                                        case 'cancelled':
                                            return new \Illuminate\Support\HtmlString('<strong style="color:red">Sewa dibatalkan.</strong>');
                                        case 'finished':
                                            return new \Illuminate\Support\HtmlString('<strong style="color:green">sudah selesai disewa dan barang sudah diterima.</strong>');
                                    }
                                }),
                            TextInput::make('note')
                                ->label('Catatan Sewa'),

                        ])
                        ->columnSpan(1),




                ])->columns(2),




        ];
    }
}
