<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserPhoneNumberResource\Pages;
use App\Filament\Resources\UserPhoneNumberResource\RelationManagers;
use App\Models\UserPhoneNumber;
use App\Filament\Imports\UserPhoneNumberImporter;
use Filament\Forms\Components;
use Filament\Tables\Actions\ImportAction;
use Filament\Notifications\Notification;

use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Rmsramos\Activitylog\Actions\ActivityLogTimelineTableAction;

class UserPhoneNumberResource extends Resource
{
    protected static ?string $model = UserPhoneNumber::class;

    protected static ?string $navigationIcon = 'heroicon-o-phone';
    protected static ?string $navigationGroup = 'User Management';

    protected static ?string $navigationLabel = 'User Phone Number';

    // protected static bool $shouldRegisterNavigation = false;

    protected static ?int $navigationSort = 12;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->label('User')
                    ->searchable()
                    ->required(),
                Forms\Components\TextInput::make('phone_number')
                    ->label('Phone Number')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([
                ImportAction::make()
                    ->importer(UserPhoneNumberImporter::class)
                    ->label('Import No Telepon'),
            ])
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('phone_number')
                    ->label('Phone Number')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('user.name')
                    ->searchable()
                    ->multiple()
                    ->preload(),

            ])
            ->actions([
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
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUserPhoneNumbers::route('/'),
            'create' => Pages\CreateUserPhoneNumber::route('/create'),
            'edit' => Pages\EditUserPhoneNumber::route('/{record}/edit'),
        ];
    }
}
