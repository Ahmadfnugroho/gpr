<?php

namespace App\Filament\Resources;


class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

   

    public static function form(Form $form): Form
    {

        return $form->schema([

            TextInput::make('booking_transaction_id')
            Grid::make('User dan Durasi')
                ->schema([
                    Select::make('user_id')
                        ->relationship('user', 'name')
                        ->columnSpan(1)

                    DateTimePicker::make('start_date')
                    Select::make('duration')
                    Placeholder::make('end_date')
                ])
                ->columns(4),


            Grid::make('Detail Transaksi')
                ->schema([
                    Repeater::make('detailTransactions')
                        ->relationship()
                        ->schema([
                            Hidden::make('uuid')
                                ->label('UUID')
                                ->default(fn() => (string) Str::uuid()),
                            Hidden::make('is_bundling')->default(false),
                            Hidden::make('bundling_id'),
                            Hidden::make('product_id'),
                            Grid::make()
                                ->columns(12)
                                ->schema([
                                    Select::make('selection_key')
                                        ->label('Pilih Produk/Bundling')
                                    TextInput::make('quantity')                   
                                    CheckboxList::make('productItems')
                                        ->columns(2)
                                        ->reactive()
                                        ->bulkToggleable()
                                        ->columnSpan(4),

                                ]),
                        ])
                        ->columns(1) // satu kolom per item repeater
                        ->grid(2)    // tampil dua item repeater per baris (di luar)
                        ->addActionLabel('Tambah Produk'),
                ]),
            Section::make('Keterangan')
                ->schema([

                    Grid::make('Pembayaran')
                        ->schema([
                            Select::make('promo_id')
                   
                                ->columnSpanFull(),

                            Placeholder::make('total_before_discount')
                                ->label('Total Sebelum Diskon')
                            Placeholder::make('discount_given')
                              

                            Placeholder::make('grand_total_display')
                  

                            Hidden::make('grand_total')
                            TextInput::make('down_payment')
                             ->columnSpanFull()
                       
                            Placeholder::make('remaining_payment')
                                ->label('Pelunasan')
                       
                            Hidden::make('remaining_payment')
                               }),
                        ])
                        ->columnSpan(1)
                        ->columns(3),
                    Grid::make('Status dan Note')
                        ->schema([
                            ToggleButtons::make('booking_status')
                    
                                ->inline()
                                ->columnSpanFull()
                                ->grouped()
                                ->reactive()
                        
                            TextInput::make('note')
                                ->label('Catatan Sewa'),



                        ])
                        ->columnSpan(1),
                ])->columns(2),
        ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('No')
                    ->size(TextColumnSize::ExtraSmall)
                    ->weight(FontWeight::Thin)
                    ->wrap()
                    ->searchable()
                    ->sortable(),

                TextColumn::make('user.name')
                    ->label('User')
                    ->wrap()
                    ->size(TextColumnSize::ExtraSmall)
                    ->weight(FontWeight::Thin)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('user_phone')
                    ->label('Phone')
                    ->getStateUsing(
                        fn(Transaction $record): string =>
                        $record->user?->userPhoneNumbers?->first()?->phone_number ?? '-'
                    )
                    ->wrap()
                    ->size(TextColumnSize::ExtraSmall)
                    ->searchable(),

                TextColumn::make('products_display')
                    ->label('Produk')
                    ->wrap()
                    ->size(TextColumnSize::ExtraSmall)
                    ->formatStateUsing(function (Transaction $record) {
                        if ($record->detailTransactions->isEmpty()) {
                            return new HtmlString('-');
                        }

                        $items = [];

                        foreach ($record->detailTransactions as $detail) {
                            $isBundling = (bool)($detail->is_bundling ?? false);

                            // Ambil customId dari detail transaksi
                            $customId = $isBundling
                                ? ($detail->bundling_id ?? null)
                                : ($detail->product_id ?? null);

                            $productName = '-';

                            if ($customId) {
                                if ($isBundling && $detail->bundling) {
                                    $productName = e($detail->bundling->name);
                                } elseif (!$isBundling && $detail->product) {
                                    $productName = e($detail->product->name);
                                }
                            }

                            // Tambahkan quantity jika tersedia
                            $quantity = $detail->quantity ?? 1;

                            $items[] = "{$productName} x{$quantity}";
                        }

                        if (empty($items)) {
                            return new HtmlString('-');
                        }

                        // Tampilkan sebagai daftar terurut
                        $html = '<ol style="margin:0; padding-left:1rem;">';
                        foreach ($items as $item) {
                            $html .= "<li>{$item}</li>";
                        }
                        $html .= '</ol>';

                        return new HtmlString($html);
                    }),
                TextColumn::make('serial_numbers')
                    ->label('Nomor Seri')
                    ->wrap()
                    ->size(TextColumnSize::ExtraSmall)
                    ->formatStateUsing(function (Transaction $record) {
                        if (!$record->detailTransactions || $record->detailTransactions->isEmpty()) {
                            return '-';
                        }

                        $serials = [];

                        foreach ($record->detailTransactions as $detail) {
                            $serialNumbers = is_array($detail->serial_numbers)
                                ? $detail->serial_numbers
                                : json_decode($detail->serial_numbers, true) ?? [];

                            foreach ($serialNumbers as $id => $serialNumber) {
                                if (is_string($serialNumber)) {
                                    $serials[] = $serialNumber;
                                } elseif (is_array($serialNumber) && isset($serialNumber['serial_number'])) {
                                    $serials[] = $serialNumber['serial_number'];
                                }
                            }
                        }

                        return $serials ? implode(', ', $serials) : '-';
                    })
                    ->toggleable(),

                TextColumn::make('booking_transaction_id')
                    ->label('Trx Id')
                    ->wrap()
                    ->size(TextColumnSize::ExtraSmall)
                    ->sortable()
                    ->searchable(),

                TextColumn::make('start_date')
                    ->label('Start')
                    ->wrap()
                    ->size(TextColumnSize::ExtraSmall)
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('end_date')
                    ->label('End')
                    ->wrap()
                    ->size(TextColumnSize::ExtraSmall)
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('grand_total')
                    ->label('Total')
                    ->wrap()
                    ->size(TextColumnSize::ExtraSmall)
                    ->formatStateUsing(fn(string $state): string => Number::currency((int)$state / 1000, 'IDR') . 'K')
                    ->sortable()
                    ->searchable(),

                TextInputColumn::make('down_payment')
                    ->label('DP')
                    ->default(fn(Transaction $record): int => (int)($record->grand_total / 2))
                    ->sortable(),

                TextColumn::make('remaining_payment')
                    ->label('Sisa')
                    ->wrap()
                    ->size(TextColumnSize::ExtraSmall)
                    ->formatStateUsing(fn(string $state): HtmlString => new HtmlString(
                        $state == '0' ? '<strong style="color: green">LUNAS</strong>' : Number::currency((int)$state / 1000, 'IDR') . 'K'
                    ))
                    ->sortable(),

                TextColumn::make('booking_status')
                    ->label('')
                    ->wrap()
                    ->size(TextColumnSize::ExtraSmall)
                    ->icon(fn(string $state): string => match ($state) {
                        'pending' => 'heroicon-o-clock',
                        'cancelled' => 'heroicon-o-x-circle',
                        'rented' => 'heroicon-o-shopping-bag',
                        'finished' => 'heroicon-o-check',
                        'paid' => 'heroicon-o-banknotes',
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'pending' => 'warning',
                        'cancelled' => 'danger',
                        'rented' => 'info',
                        'finished' => 'success',
                        'paid' => 'success',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('user.name'),
                Tables\Filters\SelectFilter::make('booking_status')
                    ->options([
                        'pending' => 'pending',
                        'cancelled' => 'cancelled',
                        'rented' => 'rented',
                        'finished' => 'finished',
                        'paid' => 'paid',
                    ]),
            ])
            ->actions([
                BulkActionGroup::make([
                    Action::make('pending')
                        ->icon('heroicon-o-clock') // Ikon untuk action
                        ->color('warning') // Warna action (warning biasanya kuning/orange)
                        ->label('Pending') // Label yang ditampilkan
                        ->requiresConfirmation() // Memastikan action memerlukan konfirmasi sebelum dijalankan
                        ->modalHeading('Ubah Status -> PENDING')
                        ->modalDescription(fn(): HtmlString => new HtmlString('Apakah Anda yakin ingin mengubah status booking menjadi Pending? <br> <strong style="color:red">Harap sesuaikan kolom DP, Jika sudah lunas maka action akan gagal</strong>')) // Deskripsi modal konfirmasi
                        ->modalSubmitActionLabel('Ya, Ubah Status') // Label tombol konfirmasi
                        ->modalCancelActionLabel('Batal') // Label tombol batal

                        ->action(function (Transaction $record) {
                            // Ambil data dari record langsung
                            $downPayment = (int) ($record->down_payment ?? 0);
                            $grandTotal = (int) ($record->grand_total ?? 0);

                            // Validasi: cek apakah DP sama dengan grand total
                            if ($downPayment === $grandTotal) {
                                Notification::make()
                                    ->danger()
                                    ->title('UBAH STATUS GAGAL')
                                    ->body('Sesuaikan DP, jika sudah lunas maka statusnya adalah "Paid", "Rented", atau "Finished"')
                                    ->send();

                                return; // Hentikan eksekusi jika kondisi tidak sesuai
                            }

                            try {
                                // Update status booking menjadi 'pending'
                                $record->update(['booking_status' => 'pending']);

                                // Notifikasi sukses
                                Notification::make()
                                    ->success()
                                    ->title('Berhasil Mengubah Status Booking Transaksi')
                                    ->send();
                            } catch (\Exception $e) {
                                // Tangani error saat update

                                Notification::make()
                                    ->danger()
                                    ->title('Gagal Update Status')
                                    ->body('Terjadi kesalahan saat memperbarui status transaksi.')
                                    ->send();
                            }
                        }),
                    Action::make('paid')
                        ->icon('heroicon-o-banknotes') // Ikon untuk action
                        ->color('success') // Warna action (success biasanya hijau)
                        ->label('Paid') // Label yang ditampilkan
                        ->requiresConfirmation() // Memastikan action memerlukan konfirmasi sebelum dijalankan
                        ->action(function (Transaction $record) {
                            // Update booking_status menjadi 'paid'
                            $record->update([
                                'booking_status' => 'paid',
                                'down_payment' => $record->grand_total, // Set down_payment sama dengan grand_total
                            ]);

                            // Notifikasi sukses
                            Notification::make()
                                ->success()
                                ->title('Berhasil Mengubah Status Transaksi')
                                ->body('Status transaksi berhasil diubah menjadi "Paid" dan down payment disesuaikan dengan grand total.')
                                ->send();
                        }),
                    Action::make('cancelled')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->label('cancelled')
                        ->requiresConfirmation()
                        ->action(function (Transaction $record) {
                            $record->update([
                                'booking_status' => 'cancelled',
                                'down_payment' => $record->grand_total, // Set down_payment sama dengan grand_total

                            ]);



                            Notification::make()
                                ->success()
                                ->title('Berhasil Mengubah Status Booking Transaksi')
                                ->send();
                        }),

                    Action::make('rented')
                        ->icon('heroicon-o-shopping-bag')
                        ->color('info')
                        ->label('rented')
                        ->requiresConfirmation()
                        ->action(function (Transaction $record) {
                            $record->update([
                                'booking_status' => 'rented',
                                'down_payment' => $record->grand_total, // Set down_payment sama dengan grand_total

                            ]);



                            Notification::make()
                                ->success()
                                ->title('Berhasil Mengubah Status Booking Transaksi')
                                ->send();
                        }),

                    Action::make('finished')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->label('finished')
                        ->requiresConfirmation()
                        ->action(function (Transaction $record) {
                            $record->update(['booking_status' => 'finished']);



                            Notification::make()
                                ->success()
                                ->title('Berhasil Mengubah Status Booking Transaksi')
                                ->send();
                        })
                ])
                    ->label('status')
                    ->size(ActionSize::ExtraSmall),



                ViewAction::make()
                    ->icon('heroicon-o-eye')
                    ->label('')
                    ->size(ActionSize::ExtraSmall),

                EditAction::make()
                    ->icon('heroicon-o-pencil')
                    ->label('')

                    ->size(ActionSize::ExtraSmall),

                DeleteAction::make()
                    ->icon('heroicon-o-trash')
                    ->label('')

                    ->size(ActionSize::ExtraSmall),
                Action::make('Invoice')
                    ->color('success')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->label('')

                    ->url(fn(Transaction $record) => route('pdf', $record))
                    ->openUrlInNewTab()
                    ->size(ActionSize::ExtraSmall),




                ActivityLogTimelineTableAction::make('Activities')
                    ->timelineIcons([
                        'created' => 'heroicon-m-check-badge',
                        'updated' => 'heroicon-m-pencil-square',
                    ])
                    ->timelineIconColors([
                        'created' => 'info',
                        'updated' => 'warning',
                    ])
                    ->label('')
                    ->icon('heroicon-m-clock'),







            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('hapus'),
                    BulkAction::make('pending')
                        ->icon('heroicon-o-clock')
                        ->color('warning')
                        ->label('Pending')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()


                        ->action(function (Collection $records) {
                            $records->each->update(['booking_status' => 'pending']);
                            Notification::make()
                                ->success()
                                ->title('Berhasil Mengubah Status Booking Transaksi')
                                ->send();
                        }),


                    BulkAction::make('paid')
                        ->icon('heroicon-o-banknotes')
                        ->color('success')
                        ->label('paid')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()


                        ->action(function (Collection $records) {
                            $records->each->update(['booking_status' => 'paid']);
                            Notification::make()
                                ->success()
                                ->title('Berhasil Mengubah Status Booking Transaksi')
                                ->send();
                        }),

                    BulkAction::make('cancelled')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->label('cancelled')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()


                        ->action(function (Collection $records) {
                            $records->each->update(['booking_status' => 'cancelled']);
                            Notification::make()
                                ->success()
                                ->title('Berhasil Mengubah Status Booking Transaksi')
                                ->send();
                        }),

                    BulkAction::make('rented')
                        ->icon('heroicon-o-shopping-bag')
                        ->color('info')
                        ->label('rented')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()


                        ->action(function (Collection $records) {
                            $records->each->update(['booking_status' => 'rented']);
                            Notification::make()
                                ->success()
                                ->title('Berhasil Mengubah Status Booking Transaksi')
                                ->send();
                        }),

                    BulkAction::make('finished')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->label('finished')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()


                        ->action(function (Collection $records) {
                            $records->each->update(['booking_status' => 'finished']);
                            Notification::make()
                                ->success()
                                ->title('Berhasil Mengubah Status Booking Transaksi')
                                ->send();
                        }),



                ]),
            ]);
    }


    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransactions::route('/'),
            'create' => Pages\CreateTransaction::route('/create'),
            'view' => Pages\ViewTransaction::route('/{record}'),

            'edit' => Pages\EditTransaction::route('/{record}/edit'),

        ];
    }
}
                            // Select::make('serial_numbers')
                            //     ->label('Pilih Serial Number')
                            //     ->visible(fn(Get $get): bool => !$get('is_bundling'))
                            //     ->searchable()
                            //     ->preload()
                            //     ->reactive()
                            //     ->options(function (Get $get) {
                            //         // Ambil product_id dari field lain di repeater ini
                            //         $productId = $get('product_id');
                            //         if (!$productId) return [];

                            //         // Ambil start_date & end_date dari form
                            //         $startDate = $get('../../start_date') ? Carbon::parse($get('../../start_date')) : now();
                            //         $endDate = $get('../../end_date') ? Carbon::parse($get('../../end_date')) : now();

                            //         // Ambil semua item produk yang tersedia di periode tersebut
                            //         $items = ProductItem::where('product_id', $productId)
                            //             ->actuallyAvailableForPeriod($startDate, $endDate)
                            //             ->pluck('serial_number', 'id');

                            //         return $items->mapWithKeys(fn($sn, $id) => [$id => $sn])->toArray();
                            //     })
                            //     ->multiple()
                            //     ->minItems(fn(Get $get) => $get('quantity') ?? 1)
                            //     ->maxItems(fn(Get $get) => $get('quantity') ?? 1)
                            //     ->helperText(fn(Get $get) => 'Pilih tepat ' . ($get('quantity') ?? 1) . ' serial number')

                            //     ->required(fn(Get $get): bool => !$get('is_bundling')),
