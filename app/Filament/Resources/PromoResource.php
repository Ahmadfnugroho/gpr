<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PromoResource\Pages;
use App\Filament\Imports\PromoImporter;
use App\Filament\Exports\PromoExporter;
use App\Models\Promo;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Actions\ImportAction;
use Filament\Tables\Actions\ExportAction;
use Filament\Tables\Table;
use Rmsramos\Activitylog\Actions\ActivityLogTimelineTableAction;

class PromoResource extends Resource
{
    protected static ?string $model = Promo::class;

    protected static ?string $navigationIcon = 'heroicon-o-gift';

    protected static ?string $navigationGroup = 'Product';

    protected static ?string $navigationLabel = 'Promo';

    // protected static bool $shouldRegisterNavigation = false;

    protected static ?int $navigationSort = 29;


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nama Promo')
                    ->required(),

                Forms\Components\Select::make('type')
                    ->label('Tipe Promo')
                    ->options([
                        'day_based' => 'Diskon Berdasarkan Hari',
                        'percentage' => 'Diskon Persentase',
                        'nominal' => 'Diskon Berdasarkan Nominal',
                    ])
                    ->required()
                    ->reactive()
                    ->default('day_based')
                    ->afterStateUpdated(function ($state, $set) {
                        $set('rules', []); // Reset rules saat tipe promo berubah
                    }),

                Forms\Components\Repeater::make('rules')
                    ->label('Aturan Promo')
                    ->schema([
                        Forms\Components\TextInput::make('group_size')
                            ->label('Jumlah Hari Sewa')
                            ->helperText('Misal: sewa 3 hari bayar 2 hari, maka isi kolom ini dengan angka 3.')
                            ->numeric()
                            ->nullable()
                            ->visible(fn($get, $state) => ($get('../../type') === 'day_based')),

                        Forms\Components\TextInput::make('pay_days')
                            ->label('Jumlah Hari Yang Dibayar')
                            ->helperText('Misal: sewa 3 hari bayar 2 hari, maka isi kolom ini dengan angka 2.')

                            ->numeric()
                            ->nullable()
                            ->visible(fn($get, $state) => ($get('../../type') === 'day_based')),

                        Forms\Components\TextInput::make('percentage')
                            ->label('Persentase Diskon (%)')
                            ->numeric()
                            ->nullable()
                            ->visible(fn($get, $state) => ($get('../../type') === 'percentage')),

                        Forms\Components\Select::make('days')
                            ->label('Hari Berlaku Diskon')
                            ->multiple()
                            ->options([
                                'Monday' => 'Senin',
                                'Tuesday' => 'Selasa',
                                'Wednesday' => 'Rabu',
                                'Thursday' => 'Kamis',
                                'Friday' => 'Jumat',
                                'Saturday' => 'Sabtu',
                                'Sunday' => 'Minggu',
                            ])
                            ->visible(fn($get, $state) => ($get('../../type') === 'percentage')),

                        Forms\Components\TextInput::make('nominal')
                            ->label('Nominal Promo (Diskon Tetap)')
                            ->helperText('Misal: Promo diskon 50rb untuk semua barang, maka isi dengan nominal 50000.')

                            ->numeric()
                            ->nullable()
                            ->visible(fn($get, $state) => ($get('../../type') === 'nominal')),
                    ])
                    ->default([])
                    ->columnSpanFull()
                    ->helperText('Isi aturan sesuai tipe promo yang dipilih.')
                    ->afterStateUpdated(function ($state) {}),

                Forms\Components\Toggle::make('active')
                    ->label('Active')
                    ->default(true)
                    ->required(),
            ]);
    }
    public static function table(Table $table): Table
    {
        return $table
            ->defaultPaginationPageOption(50)
            ->headerActions([
                ImportAction::make()
                    ->importer(PromoImporter::class)
                    ->label('Import Promo'),
                ExportAction::make()
                    ->exporter(PromoExporter::class)
                    ->label('Export Promo'),
            ])
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Promo')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Tipe Promo')
                    ->sortable(),

                ToggleColumn::make('active')
                    ->label('Active'),
            ])
            ->filters([
                Tables\Filters\Filter::make('name')
                    ->label('Nama Promo'),
                Tables\Filters\Filter::make('active')
                    ->label('Active'),
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipe Promo')
                    ->options([
                        'day_based' => 'Diskon Berdasarkan Hari',
                        'percentage' => 'Diskon Persentase',
                        'nominal' => 'Diskon Berdasarkan Nominal',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                ActivityLogTimelineTableAction::make('Activities')
                    ->timelineIcons([
                        'created' => 'heroicon-m-check-badge',
                        'updated' => 'heroicon-m-pencil-square',
                    ])
                    ->timelineIconColors([
                        'created' => 'info',
                        'updated' => 'warning',
                    ]),

            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPromos::route('/'),
            'create' => Pages\CreatePromo::route('/create'),
            'edit' => Pages\EditPromo::route('/{record}/edit'),
        ];
    }
}
