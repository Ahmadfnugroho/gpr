<?php

namespace App\Filament\Resources\TransactionResource\TableActions;

use App\Models\Transaction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Support\Enums\ActionSize;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Illuminate\Support\HtmlString;
use Rmsramos\Activitylog\Actions\ActivityLogTimelineTableAction;

class StatusActions
{
    public static function getActions(): array
    {
        return [
            BulkActionGroup::make([
                Action::make('booking')
                    ->icon('heroicon-o-clock') // Ikon untuk action
                    ->color('warning') // Warna action (warning biasanya kuning/orange)
                    ->label('booking') // Label yang ditampilkan
                    ->requiresConfirmation() // Memastikan action memerlukan konfirmasi sebelum dijalankan
                    ->modalHeading('Ubah Status -> booking')
                    ->modalDescription(fn(): HtmlString => new HtmlString('Apakah Anda yakin ingin mengubah status booking menjadi booking? <br> <strong style="color:red">Harap sesuaikan kolom DP, Jika sudah lunas maka action akan gagal</strong>')) // Deskripsi modal konfirmasi
                    ->modalSubmitActionLabel('Ya, Ubah Status') // Label tombol konfirmasi
                    ->modalCancelActionLabel('Batal') // Label tombol batal

                    ->action(function (Transaction $record, ?array $get = null) {
                        // Cek apakah down payment sama dengan grand total
                        $downPayment = (int) ($get['down_payment'] ?? 0);
                        $grandTotal = (int) ($get['grand_total'] ?? 0);

                        if ($downPayment === $grandTotal) {
                            // Notifikasi peringatan dan hentikan proses action
                            Notification::make()
                                ->danger()
                                ->title('UBAH STATUS GAGAL')
                                ->body('Sesuaikan DP, jika sudah lunas maka statusnya adalah "Paid atau on_rented atau done"')
                                ->send();

                            // Gagalkan proses action
                            return;
                        }

                        // Update status booking menjadi 'booking' jika kondisi di atas tidak terpenuhi
                        $record->update(['booking_status' => 'booking']);

                        // Notifikasi sukses
                        Notification::make()
                            ->success()
                            ->title('Berhasil Mengubah Status Booking Transaksi')
                            ->send();
                    }),
                Action::make('paid')
                    ->icon('heroicon-o-banknotes') // Ikon untuk action
                    ->color('success') // Warna action (success biasanya hijau)
                    ->label('Paid') // Label yang ditampilkan
                    ->requiresConfirmation() // Memastikan action memerlukan konfirmasi sebelum dijalankan
                    ->action(function (Transaction $record) {
                        // DATABASE VALUES ONLY - NO CALCULATIONS OR OVERRIDES
                        // Only change booking status, preserve all financial values
                        $record->update(['booking_status' => 'paid']);

                        // Notifikasi sukses
                        Notification::make()
                            ->success()
                            ->title('Berhasil Mengubah Status Transaksi')
                            ->body('Status transaksi berhasil diubah menjadi "Paid" dan down payment disesuaikan dengan grand total.')
                            ->send();
                    }),
                Action::make('cancel')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->label('cancel')
                    ->requiresConfirmation()
                    ->action(function (Transaction $record) {
                        // DATABASE VALUES ONLY - NO CALCULATIONS OR OVERRIDES
                        // Only change booking status, preserve all financial values
                        $record->update(['booking_status' => 'cancel']);



                        Notification::make()
                            ->success()
                            ->title('Berhasil Mengubah Status Booking Transaksi')
                            ->send();
                    }),

                Action::make('on_rented')
                    ->icon('heroicon-o-shopping-bag')
                    ->color('info')
                    ->label('on_rented')
                    ->requiresConfirmation()
                    ->action(function (Transaction $record) {
                        // DATABASE VALUES ONLY - NO CALCULATIONS OR OVERRIDES
                        // Only change booking status, preserve all financial values
                        $record->update(['booking_status' => 'on_rented']);



                        Notification::make()
                            ->success()
                            ->title('Berhasil Mengubah Status Booking Transaksi')
                            ->send();
                    }),

                Action::make('done')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->label('done')
                    ->requiresConfirmation()
                    ->action(function (Transaction $record) {
                        $record->update(['booking_status' => 'done']);



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







        ];
    }
}
