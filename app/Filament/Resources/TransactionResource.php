<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransactionResource\FormSections\PaymentStatusSection;
use App\Filament\Resources\TransactionResource\FormSections\ProductListSection;
use App\Filament\Resources\TransactionResource\FormSections\UserAndDurationSection;
use App\Filament\Resources\TransactionResource\Pages;
use App\Filament\Resources\TransactionResource\TableActions\StatusActions;
use App\Filament\Resources\TransactionResource\TableActions\TransactionBulkActions;
use App\Filament\Resources\TransactionResource\TableColumns\TransactionProductColumn;
use App\Models\Bundling;
use App\Models\DetailTransaction;
use App\Models\Product;
use App\Models\Transaction;
use Filament\Forms;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class Number
{
    public static function currency($amount, $currency)
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }
}
class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;
    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
    protected static ?string $navigationGroup = 'Transaction';
    protected static ?string $navigationLabel = 'Transaction';
    protected static ?int $navigationSort = 31;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('booking_transaction_id')
                    ->label('Booking Trx Id')
                    ->disabled(),
                ...UserAndDurationSection::getSchema(),
                ...ProductListSection::getSchema(),
                ...PaymentStatusSection::getSchema(),

            ]);
    }

    public static function getEagerLoadRelations(): array
    {
        return [
            'user.userPhoneNumbers',
            'DetailTransactions.product',
            'DetailTransactions.bundling.products',
            'DetailTransactions.productTransactions.productItem',
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(TransactionProductColumn::get())

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
            ->actions(StatusActions::getActions())


            ->bulkActions(TransactionBulkActions::get());
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
