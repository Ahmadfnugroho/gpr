<?php

namespace App\Filament\Resources\TransactionResource\TableActions;

use Illuminate\Support\Collection;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\DeleteBulkAction;

class TransactionBulkActions
{
    public static function get(): array
    {
        return [
            DeleteBulkAction::make()
                ->label('hapus'),
            BulkAction::make('booking')
                ->icon('heroicon-o-clock')
                ->color('warning')
                ->label('booking')
                ->requiresConfirmation()
                ->deselectRecordsAfterCompletion()


                ->action(function (Collection $records) {
                    $records->each->update(['booking_status' => 'booking']);
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

            BulkAction::make('cancel')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->label('cancel')
                ->requiresConfirmation()
                ->deselectRecordsAfterCompletion()


                ->action(function (Collection $records) {
                    $records->each->update(['booking_status' => 'cancel']);
                    Notification::make()
                        ->success()
                        ->title('Berhasil Mengubah Status Booking Transaksi')
                        ->send();
                }),

            BulkAction::make('on_rented')
                ->icon('heroicon-o-shopping-bag')
                ->color('info')
                ->label('on_rented')
                ->requiresConfirmation()
                ->deselectRecordsAfterCompletion()


                ->action(function (Collection $records) {
                    $records->each->update(['booking_status' => 'on_rented']);
                    Notification::make()
                        ->success()
                        ->title('Berhasil Mengubah Status Booking Transaksi')
                        ->send();
                }),

            BulkAction::make('done')
                ->icon('heroicon-o-check')
                ->color('success')
                ->label('done')
                ->requiresConfirmation()
                ->deselectRecordsAfterCompletion()


                ->action(function (Collection $records) {
                    $records->each->update(['booking_status' => 'done']);
                    Notification::make()
                        ->success()
                        ->title('Berhasil Mengubah Status Booking Transaksi')
                        ->send();
                }),



        ];
    }
}
