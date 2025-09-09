<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PromoResource\Pages;
use App\Filament\Imports\PromoImporter;
use App\Filament\Exports\PromoExporter;
use App\Models\Promo;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
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
                // Nama Promo
                Forms\Components\TextInput::make('name')
                    ->label('Nama Promo')
                    ->required()
                    ->columnSpanFull(),

                // Kode Promo
                Forms\Components\TextInput::make('code')
                    ->label('Kode Promo')
                    ->helperText('Kosongkan untuk generate otomatis (PROMO-XXXXXX)')
                    ->unique(Promo::class, 'code', ignoreRecord: true)
                    ->maxLength(50)
                    ->columnSpanFull(),

                // Tipe Promo
                Forms\Components\Select::make('type')
                    ->label('Tipe Promo')
                    ->options([
                        'percentage' => 'Percentage',
                        'nominal' => 'Nominal',
                        'day_based' => 'Day Based',
                    ])
                    ->required()
                    ->live()
                    ->default('percentage')
                    ->columnSpanFull(),

                // Fields untuk percentage
                Forms\Components\TextInput::make('percentage')
                    ->label('Persentase Diskon (%)')
                    ->numeric()
                    ->visible(fn(Get $get) => $get('type') === 'percentage')
                    ->required(fn(Get $get) => $get('type') === 'percentage')
                    ->dehydrated(false)
                    ->afterStateHydrated(function ($state, Set $set, Get $get) {
                        $rules = $get('rules');
                        if (is_array($rules) && array_key_exists('percentage', $rules)) {
                            $set('percentage', $rules['percentage']);
                        }
                    }),
                Forms\Components\Select::make('percentage_days')
                    ->label('Hari Berlaku (Percentage)')
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
                    ->visible(fn(Get $get) => $get('type') === 'percentage')
                    ->dehydrated(false)
                    ->afterStateHydrated(function ($state, Set $set, Get $get) {
                        $rules = $get('rules');
                        if (is_array($rules) && array_key_exists('days', $rules) && $get('type') === 'percentage') {
                            $set('percentage_days', $rules['days']);
                        }
                    }),

                // Fields untuk nominal
                Forms\Components\TextInput::make('nominal')
                    ->label('Nominal Diskon (Rp)')
                    ->numeric()
                    ->visible(fn(Get $get) => $get('type') === 'nominal')
                    ->required(fn(Get $get) => $get('type') === 'nominal')
                    ->dehydrated(false)
                    ->afterStateHydrated(function ($state, Set $set, Get $get) {
                        $rules = $get('rules');
                        if (is_array($rules) && array_key_exists('nominal', $rules)) {
                            $set('nominal', $rules['nominal']);
                        }
                    }),
                Forms\Components\Select::make('nominal_days')
                    ->label('Hari Berlaku (Nominal)')
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
                    ->visible(fn(Get $get) => $get('type') === 'nominal')
                    ->dehydrated(false)
                    ->afterStateHydrated(function ($state, Set $set, Get $get) {
                        $rules = $get('rules');
                        if (is_array($rules) && array_key_exists('days', $rules) && $get('type') === 'nominal') {
                            $set('nominal_days', $rules['days']);
                        }
                    }),

                // Fields untuk day_based
                Forms\Components\TextInput::make('group_size')
                    ->label('Jumlah Hari Sewa (group_size)')
                    ->numeric()
                    ->visible(fn(Get $get) => $get('type') === 'day_based')
                    ->required(fn(Get $get) => $get('type') === 'day_based')
                    ->dehydrated(false)
                    ->afterStateHydrated(function ($state, Set $set, Get $get) {
                        $rules = $get('rules');
                        if (is_array($rules) && array_key_exists('group_size', $rules)) {
                            $set('group_size', $rules['group_size']);
                        }
                    }),
                Forms\Components\TextInput::make('pay_days')
                    ->label('Jumlah Hari Dibayar (pay_days)')
                    ->numeric()
                    ->visible(fn(Get $get) => $get('type') === 'day_based')
                    ->required(fn(Get $get) => $get('type') === 'day_based')
                    ->dehydrated(false)
                    ->afterStateHydrated(function ($state, Set $set, Get $get) {
                        $rules = $get('rules');
                        if (is_array($rules) && array_key_exists('pay_days', $rules)) {
                            $set('pay_days', $rules['pay_days']);
                        }
                    }),

                // Hidden field untuk menyimpan JSON rules yang dirangkai dari input di atas
                Forms\Components\Hidden::make('rules')
                    ->default([])
                    ->dehydrateStateUsing(function (Get $get) {
                        $type = $get('type');
                        $rules = match ($type) {
                            'percentage' => [
                                'percentage' => (float) ($get('percentage') ?? 0),
                                'days' => (array) ($get('percentage_days') ?? []),
                            ],
                            'nominal' => [
                                'nominal' => (int) ($get('nominal') ?? 0),
                                'days' => (array) ($get('nominal_days') ?? []),
                            ],
                            'day_based' => [
                                'group_size' => (int) ($get('group_size') ?? 1),
                                'pay_days' => (int) ($get('pay_days') ?? 0),
                            ],
                            default => [],
                        };
                        
                        // Log untuk debugging
                        \Illuminate\Support\Facades\Log::info('PromoResource - dehydrateStateUsing', [
                            'type' => $type,
                            'rules' => $rules,
                            'raw_data' => [
                                'percentage' => $get('percentage'),
                                'nominal' => $get('nominal'),
                                'group_size' => $get('group_size'),
                                'pay_days' => $get('pay_days'),
                            ]
                        ]);
                        
                        return $rules;
                    }),

                // Active toggle
                Forms\Components\Toggle::make('active')
                    ->label('Active')
                    ->default(true)
                    ->required()
                    ->columnSpanFull(),
            ]);
    }
    public static function table(Table $table): Table
    {
        return $table
            ->defaultPaginationPageOption(50)
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Promo')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('code')
                    ->label('Kode Promo')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

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
