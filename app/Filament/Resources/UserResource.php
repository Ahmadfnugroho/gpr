<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Filament\Imports\UserImporter;
use App\Filament\Exports\UserExporter;

use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Filament\Tables\Actions\ExportAction;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Tables\Actions\ImportAction;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Http as FacadesHttp;
use Rmsramos\Activitylog\Actions\ActivityLogTimelineTableAction;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $recordTitleAttribute = 'name';

    // ✅ Global Search Configuration
    public static function getGlobalSearchResultTitle($record): string
    {
        return $record->name;
    }
    
    public static function getGloballySearchableAttributes(): array
    {
        return [
            'name',
            'email',
            'userPhoneNumbers.phone_number',
        ];
    }
    
    public static function getGlobalSearchEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getGlobalSearchEloquentQuery()
            ->with(['userPhoneNumbers:id,user_id,phone_number'])
            ->where('status', '!=', 'deleted');
    }
    
    public static function getGlobalSearchResultDetails($record): array
    {
        $phone = $record->userPhoneNumbers->first();
        return [
            'Email' => $record->email,
            'Phone' => $phone ? $phone->phone_number : '-',
            'Status' => $record->status === 'active' ? '✅ Active' : '❌ Blacklist',
        ];
    }

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'User Management';

    protected static ?string $navigationLabel = 'Users';

    // protected static bool $shouldRegisterNavigation = false;

    protected static ?int $navigationSort = 11;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([


                Forms\Components\Section::make([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\CheckboxList::make('roles')
                        ->relationship('roles', 'name')
                        ->searchable(),
                    Forms\Components\TextInput::make('email')
                        ->required()
                        ->email()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('password')
                        ->default(function () {
                            return Str::random(8);
                        },)
                        ->password()
                        ->hidden(),
                    Forms\Components\TextInput::make('address'),
                    Forms\Components\TextInput::make('job')
                        ->maxLength(255),
                    Forms\Components\TextInput::make('office_address'),
                    Forms\Components\TextInput::make('instagram_username')
                        ->maxLength(255),
                    Forms\Components\TextInput::make('facebook_username')
                        ->maxLength(255),
                    Forms\Components\TextInput::make('emergency_contact_name')
                        ->maxLength(255),
                    Forms\Components\TextInput::make('emergency_contact_number')
                        ->maxLength(255),
                    Forms\Components\Select::make('gender')
                        ->options([
                            'male' => 'Male',
                            'female' => 'Female'
                        ]),
                    Forms\Components\Select::make('status')
                        ->options([
                            'active' => 'Active',
                            'blacklist' => 'Blacklist'
                        ])
                        ->default('blacklist'),
                    Forms\Components\TextInput::make('source_info')
                        ->maxLength(255),
                ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([
                ExportAction::make()

                    ->exporter(UserExporter::class)
                    ->formats([
                        ExportFormat::Xlsx,
                    ]),

                // Tombol Import Produk
                ImportAction::make()
                    ->importer(UserImporter::class)
                    ->label('Import User'),

                // Tables\Actions\Action::make('Import dari Google Sheets')
                //     ->color('primary')
                //     ->icon('heroicon-o-arrow-down-tray')
                //     ->requiresConfirmation()
                //     ->action(function () {
                //         $response = FacadesHttp::get(route('sync'));

                //         if ($response->json('redirect')) {
                //             $this->dispatchBrowserEvent('open-new-tab', ['url' => $response->json('redirect')]);
                //             return;
                //         }

                //         if ($response->successful()) {
                //             Notification::make()
                //                 ->title('Import Berhasil')
                //                 ->body('Data pengguna berhasil diimpor dari Google Sheets.')
                //                 ->success()
                //                 ->send();
                //         } else {
                //             Notification::make()
                //                 ->title('Import Gagal')
                //                 ->body('Terjadi kesalahan saat mengimpor data: ' . $response->body())
                //                 ->danger()
                //                 ->send();
                //         }
                //     }),


            ])

            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('userPhoneNumbers.phone_number')
                    ->label('Phone Number')
                    ->sortable(),
                Tables\Columns\IconColumn::make('status')
                    ->label('Status Keanggotaan')
                    ->getStateUsing(fn($record) => $record->status === 'active')
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),


            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkActionGroup::make([
                        Tables\Actions\Action::make('active')
                            ->icon('heroicon-o-check-circle')
                            ->color('success')
                            ->label('active')
                            ->requiresConfirmation()
                            ->action(function (User $record) {
                                $record->update(['status' => 'active']);
                                Notification::make()
                                    ->success()
                                    ->title('Berhasil Mengubah Status User')
                                    ->send();
                            }),
                        Tables\Actions\Action::make('blacklist')
                            ->icon('heroicon-o-x-circle')
                            ->color('danger')
                            ->label('blacklist')
                            ->requiresConfirmation()
                            ->action(function (User $record) {
                                $record->update(['status' => 'blacklist']);
                                Notification::make()
                                    ->success()
                                    ->title('Berhasil Mengubah Status User')
                                    ->send();
                            })
                    ])
                        ->label('Ubah Status User'),
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),


                ])
                    ->label('Lihat/Ubah User')
                    ->icon('heroicon-o-eye'),
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
                    Tables\Actions\BulkActionGroup::make([
                        Tables\Actions\Action::make('active')
                            ->icon('heroicon-o-check-circle')
                            ->color('success')
                            ->label('active')
                            ->requiresConfirmation()
                            ->action(function (User $record) {
                                $record->update(['status' => 'active']);
                                Notification::make()
                                    ->success()
                                    ->title('Berhasil Mengubah Status User')
                                    ->send();
                            }),
                        Tables\Actions\Action::make('blacklist')
                            ->icon('heroicon-o-x-circle')
                            ->color('danger')
                            ->label('blacklist')
                            ->requiresConfirmation()
                            ->action(function (User $record) {
                                $record->update(['status' => 'blacklist']);
                                Notification::make()
                                    ->success()
                                    ->title('Berhasil Mengubah Status User')
                                    ->send();
                            })
                    ])
                        ->label('Ubah Status User'),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\UserPhoneNumberRelationManager::class,
            RelationManagers\UserPhotoRelationManager::class

        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),

        ];
    }
}
