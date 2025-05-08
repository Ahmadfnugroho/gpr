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



        ];
    }
}
