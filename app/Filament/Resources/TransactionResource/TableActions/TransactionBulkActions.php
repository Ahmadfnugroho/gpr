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
                    // Update each record individually to trigger observers and proper calculations
                    $updatedCount = 0;
                    foreach ($records as $record) {
                        try {
                            // Load relations needed for calculation
                            $record->load(['detailTransactions.product', 'detailTransactions.bundling', 'promo']);
                            
                            // Update status and let observers handle grand_total calculation
                            $record->booking_status = 'booking';
                            $record->save();
                            $updatedCount++;
                        } catch (\Exception $e) {
                            \Illuminate\Support\Facades\Log::error('BulkAction booking failed', [
                                'transaction_id' => $record->id,
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
                    
                    Notification::make()
                        ->success()
                        ->title('Berhasil Mengubah Status ' . $updatedCount . ' Transaksi ke Booking')
                        ->send();
                }),


            BulkAction::make('paid')
                ->icon('heroicon-o-banknotes')
                ->color('success')
                ->label('paid')
                ->requiresConfirmation()
                ->deselectRecordsAfterCompletion()


                ->action(function (Collection $records) {
                    // Update each record individually to trigger observers and proper calculations
                    $updatedCount = 0;
                    foreach ($records as $record) {
                        try {
                            // Load relations needed for calculation
                            $record->load(['detailTransactions.product', 'detailTransactions.bundling', 'promo']);
                            
                            // Update status and let observers handle grand_total calculation
                            $record->booking_status = 'paid';
                            $record->save();
                            $updatedCount++;
                        } catch (\Exception $e) {
                            \Illuminate\Support\Facades\Log::error('BulkAction paid failed', [
                                'transaction_id' => $record->id,
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
                    
                    Notification::make()
                        ->success()
                        ->title('Berhasil Mengubah Status ' . $updatedCount . ' Transaksi ke Paid')
                        ->send();
                }),

            BulkAction::make('cancel')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->label('cancel')
                ->requiresConfirmation()
                ->deselectRecordsAfterCompletion()


                ->action(function (Collection $records) {
                    // Update each record individually to trigger observers and proper calculations
                    $updatedCount = 0;
                    foreach ($records as $record) {
                        try {
                            // Load relations needed for calculation
                            $record->load(['detailTransactions.product', 'detailTransactions.bundling', 'promo']);
                            
                            // Update status and let observers handle grand_total calculation
                            $record->booking_status = 'cancel';
                            $record->save();
                            $updatedCount++;
                        } catch (\Exception $e) {
                            \Illuminate\Support\Facades\Log::error('BulkAction cancel failed', [
                                'transaction_id' => $record->id,
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
                    
                    Notification::make()
                        ->success()
                        ->title('Berhasil Mengubah Status ' . $updatedCount . ' Transaksi ke Cancel')
                        ->send();
                }),

            BulkAction::make('on_rented')
                ->icon('heroicon-o-shopping-bag')
                ->color('info')
                ->label('on_rented')
                ->requiresConfirmation()
                ->deselectRecordsAfterCompletion()


                ->action(function (Collection $records) {
                    // Update each record individually to trigger observers and proper calculations
                    $updatedCount = 0;
                    foreach ($records as $record) {
                        try {
                            // Load relations needed for calculation
                            $record->load(['detailTransactions.product', 'detailTransactions.bundling', 'promo']);
                            
                            // Update status and let observers handle grand_total calculation
                            $record->booking_status = 'on_rented';
                            $record->save();
                            $updatedCount++;
                        } catch (\Exception $e) {
                            \Illuminate\Support\Facades\Log::error('BulkAction on_rented failed', [
                                'transaction_id' => $record->id,
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
                    
                    Notification::make()
                        ->success()
                        ->title('Berhasil Mengubah Status ' . $updatedCount . ' Transaksi ke On Rented')
                        ->send();
                }),

            BulkAction::make('done')
                ->icon('heroicon-o-check')
                ->color('success')
                ->label('done')
                ->requiresConfirmation()
                ->deselectRecordsAfterCompletion()


                ->action(function (Collection $records) {
                    // Update each record individually to trigger observers and proper calculations
                    $updatedCount = 0;
                    foreach ($records as $record) {
                        try {
                            // Load relations needed for calculation
                            $record->load(['detailTransactions.product', 'detailTransactions.bundling', 'promo']);
                            
                            // Update status and let observers handle grand_total calculation
                            $record->booking_status = 'done';
                            $record->save();
                            $updatedCount++;
                        } catch (\Exception $e) {
                            \Illuminate\Support\Facades\Log::error('BulkAction done failed', [
                                'transaction_id' => $record->id,
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
                    
                    Notification::make()
                        ->success()
                        ->title('Berhasil Mengubah Status ' . $updatedCount . ' Transaksi ke Done')
                        ->send();
                }),



        ];
    }
}
