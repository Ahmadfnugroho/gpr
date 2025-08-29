<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserPhotoResource\Pages;
use App\Filament\Resources\UserPhotoResource\RelationManagers;
use App\Models\UserPhoto;
use App\Models\User;
use App\Filament\Imports\UserPhotoImporter;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Actions\ImportAction;
use Filament\Notifications\Notification;
use Rmsramos\Activitylog\Actions\ActivityLogTimelineTableAction;

class UserPhotoResource extends Resource
{
    protected static ?string $model = UserPhoto::class;

    protected static ?string $navigationIcon = 'heroicon-o-photo';
    protected static ?string $navigationGroup = 'User Management';

    protected static ?string $navigationLabel = 'User Photo';

    // protected static bool $shouldRegisterNavigation = false;

    protected static ?int $navigationSort = 13;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([

                Forms\Components\FileUpload::make('photo')
                    ->image()
                    ->required(),

                Forms\Components\Select::make('photo_type')
                    ->options([
                        'Kartu Keluarga' => 'Kartu Keluarga',
                        'SIM' => 'SIM',
                        'NPWP' => 'NPWP',
                        'STNK' => 'STNK',
                        'BPKB' => 'BPKB',
                        'Passport' => 'Passport',
                        'BPJS' => 'BPJS',
                        'ID Card Kerja' => 'ID Card Kerja',
                        'KTP' => 'KTP',
                        'Screenshot Follow' => 'Screenshot Follow',
                    ])
                    ->nullable(),
                Forms\Components\Select::make('user_id')
                    ->label('User')
                    ->required()
                    ->relationship('user', 'name')
                    ->searchable(),



            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultPaginationPageOption(50)
            ->headerActions([
                ImportAction::make()
                    ->importer(UserPhotoImporter::class)
                    ->label('Import User Photo'),
            ])
            ->columns([
                Tables\Columns\ImageColumn::make('photo')
                    ->label('Preview')
                    ->disk('public')
                    ->height(80)
                    ->width(80)
                    ->defaultImageUrl('/images/placeholder.png'),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('photo_type')
                    ->label('Jenis Foto')
                    ->badge()
                    ->color(function ($record) {
                        return match($record->photo_type) {
                            'KTP' => 'success',
                            'ktp' => 'success', 
                            'additional_id' => 'info',
                            default => 'gray'
                        };
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Diupload')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('view_full_photo')
                    ->label('Lihat Besar')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading(fn($record) => 'Foto ' . ($record->photo_type ?: 'Dokumen') . ' - ' . $record->user->name)
                    ->modalContent(fn($record) => view('filament.resources.user-photo-resource.pages.view-full-photo', ['userPhoto' => $record]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup'),
                Tables\Actions\ViewAction::make()
                    ->label('Detail'),
                Tables\Actions\EditAction::make()
                    ->hidden(), // Hide edit to prevent accidental deletion
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
            'index' => Pages\ListUserPhotos::route('/'),
            'create' => Pages\CreateUserPhoto::route('/create'),
            'edit' => Pages\EditUserPhoto::route('/{record}/edit'),
        ];
    }
}
