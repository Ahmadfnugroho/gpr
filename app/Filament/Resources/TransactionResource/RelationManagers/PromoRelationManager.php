<?php

namespace App\Filament\Resources\TransactionResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;

class PromoRelationManager extends RelationManager
{
    protected static string $relationship = 'promo';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('code')
                    ->label('Kode Promo')
                    ->required()
                    ->maxLength(255)
                    ->unique()
                    ->helperText('Gunakan kode yang unik dan mudah diingat.'),

                Select::make('discount_type')
                    ->label('Tipe Diskon')
                    ->options([
                        'fixed' => 'Diskon Tetap (Rp)',
                        'percent' => 'Persentase (%)',
                    ])
                    ->required()
                    ->reactive(),

                TextInput::make('value')
                    ->label('Nilai Diskon')
                    ->numeric()
                    ->required()
                    ->helperText(fn ($get) => $get('discount_type') === 'fixed' ? 'Contoh: 100000 (Rp100.000)' : 'Contoh: 10 (10%)'),

                DatePicker::make('valid_from')
                    ->label('Berlaku Mulai')
                    ->required()
                    ->default(now()),

                DatePicker::make('valid_to')
                    ->label('Berlaku Sampai')
                    ->required()
                    ->afterOrEqual('valid_from'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('code')
            ->columns([
                TextColumn::make('code')
                    ->label('Kode Promo')
                    ->searchable()
                    ->icon('heroicon-o-tag')
                    ->sortable(),

                TextColumn::make('discount_type')
                    ->label('Tipe Diskon')
                    ->formatStateUsing(fn(string $state): string => ucwords($state))
                    ->badge()
                    ->color(fn(string $state): string => $state === 'fixed' ? 'success' : 'warning')
                    ->sortable(),

                TextColumn::make('value')
                    ->label('Nilai Diskon')
                    ->formatStateUsing(fn($state, Model $record) =>
                        $record->discount_type === 'fixed'
                            ? 'Rp ' . number_format($state, 0, ',', '.')
                            : $state . '%'
                    )
                    ->alignRight()
                    ->sortable(),

                TextColumn::make('valid_from')
                    ->label('Berlaku Mulai')
                    ->date()
                    ->icon('heroicon-o-calendar')
                    ->sortable(),

                TextColumn::make('valid_to')
                    ->label('Berlaku Sampai')
                    ->date()
                    ->icon('heroicon-o-calendar')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->preloadRecordSelect()
                    ->action(function (array $data, callable $attach) {
                        $promo = \App\Models\Promo::find($data['record']);
                        if ($promo && $promo->is_active) {
                            $attach($promo->id);
                        } else {
                            Notification::make()
                                ->danger()
                                ->title('Promo tidak aktif')
                                ->send();
                        }
                    }),
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DetachAction::make(),
            ]);
    }
}
