<?php

namespace App\Filament\Resources\TransactionResource\RelationManagers;

use App\Filament\Resources\TransactionResource\FormSections\ProductList\Number;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use App\Models\DetailTransaction;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Get;

class DetailTransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'detailTransactions';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('type')
                    ->label('Tipe Item')
                    ->options([
                        'product' => 'Produk',
                        'bundling' => 'Bundling'
                    ])
                    ->default('product')
                    ->live()
                    ->required(),

                Select::make('product_id')
                    ->label('Produk')
                    ->searchable()
                    ->preload()
                    ->hidden(fn(Get $get) => $get('type') !== 'product')
                    ->required(fn(Get $get) => $get('type') === 'product')
                    ->options(function (?DetailTransaction $record) {
                        if (! $record) {
                            return [];
                        }
                        return $record->product()->pluck('name', 'id');
                    }),

                Select::make('bundling_id')
                    ->label('Bundling')
                    ->searchable()
                    ->preload()
                    ->hidden(fn(Get $get) => $get('type') !== 'bundling')
                    ->required(fn(Get $get) => $get('type') === 'bundling')
                    ->options(function (?DetailTransaction $record) {
                        if (! $record) {
                            return [];
                        }
                        return $record->bundling()->pluck('name', 'id');
                    }),

                TextInput::make('quantity')
                    ->label('Jumlah')
                    ->numeric()
                    ->reactive()
                    ->required()
                    ->live()
                    ->maxValue(fn(Get $get) => self::calculateMaxQuantity($get))
                    ->minValue(1)
                    ->afterStateUpdated(function ($state, callable $set, $get) {
                        $price = $get('price');
                        if ($price && is_numeric($price)) {
                            $set('total_price', $price * $state);
                        }
                    })
                    ->stateRules([
                        \Illuminate\Validation\Rule::requiredIf(fn() => true),
                        function (\Closure $get) {
                            return function (string $attribute, $value, \Closure $fail) use ($get) {
                                $type = $get('type');
                                $productId = $get('product_id');
                                $bundlingId = $get('bundling_id');

                                if ($type === 'product' && empty($productId)) {
                                    $fail('Silakan pilih produk.');
                                }

                                if ($type === 'bundling' && empty($bundlingId)) {
                                    $fail('Silakan pilih bundling.');
                                }

                                if (!empty($productId) && !empty($bundlingId)) {
                                    $fail('Hanya salah satu antara produk atau bundling yang boleh dipilih.');
                                }
                            };
                        },
                    ]),

                TextInput::make('price')
                    ->label('Harga Satuan')
                    ->prefix('Rp')
                    ->numeric()
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set, $get) {
                        $quantity = $get('quantity');
                        if ($quantity && is_numeric($quantity)) {
                            $set('total_price', $state * $quantity);
                        }
                    }),

                TextInput::make('total_price')
                    ->label('Total Harga')
                    ->prefix('Rp')
                    ->disabled()
                    ->dehydrated()
                    ->numeric()
                    ->required(),

                Repeater::make('serial_numbers')
                    ->label('Nomor Seri')
                    ->schema([
                        TextInput::make('value')
                            ->label('Serial Number')
                            ->required()
                    ])
                    ->columns(2)
                    ->addActionLabel('Tambah Serial')
                    ->minItems(fn(callable $get) => $get('quantity'))
                    ->maxItems(fn(callable $get) => $get('quantity')),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('product.name')
            ->columns([
                TextColumn::make('product.name')
                    ->label('Produk')
                    ->icon('heroicon-o-cube')
                    ->toggleable()
                    ->searchable(),

                TextColumn::make('bundling.name')
                    ->label('Bundling')
                    ->icon('heroicon-o-briefcase')
                    ->toggleable()
                    ->searchable(),

                TextColumn::make('quantity')
                    ->label('Jumlah')
                    ->icon('heroicon-o-hashtag')
                    ->alignCenter(),

                TextColumn::make('price')
                    ->label('Harga Satuan')
                    ->formatStateUsing(fn($state) => Number::currency($state, 'IDR'))
                    ->icon('heroicon-o-currency-dollar')
                    ->alignRight(),

                TextColumn::make('total_price')
                    ->label('Total')
                    ->formatStateUsing(fn($state) => Number::currency($state, 'IDR'))
                    ->icon('heroicon-o-banknotes')
                    ->alignRight(),

                TextColumn::make('serial_numbers')
                    ->label('Serial Number')
                    ->formatStateUsing(fn($state) => is_array($state) ? implode(', ', $state) : '-')
                    ->wrap()
                    ->icon('heroicon-o-key'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
