<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use App\Models\UserPhoto;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\Model;

class UserPhotoRelationManager extends RelationManager
{
    protected static string $relationship = 'userPhotos';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\FileUpload::make('photo')
                    ->image()
                    ->directory('user_photos')
                    ->disk('public')
                    ->imageResizeMode('cover')
                    ->imageCropAspectRatio('4:3')
                    ->imageResizeTargetWidth(800)
                    ->imageResizeTargetHeight(600)
                    ->required(),

                Forms\Components\Select::make('photo_type')
                    ->options([
                        'ktp' => '📄 KTP',
                        'additional_id_1' => '🆔 ID Tambahan 1',
                        'additional_id_2' => '🆔 ID Tambahan 2',
                        'additional_id' => '🆔 ID Tambahan',
                    ])
                    ->required()
                    ->live(),
                    
                Forms\Components\Select::make('id_type')
                    ->label('Jenis ID (untuk ID Tambahan)')
                    ->options([
                        'KK' => 'Kartu Keluarga (KK)',
                        'SIM' => 'SIM (Surat Izin Mengemudi)',
                        'NPWP' => 'NPWP',
                        'STNK' => 'STNK',
                        'BPKB' => 'BPKB',
                        'Passport' => 'Passport',
                        'BPJS' => 'BPJS',
                        'ID_Kerja' => 'ID Card Kerja',
                    ])
                    ->nullable()
                    ->visible(fn(callable $get) => in_array($get('photo_type'), ['additional_id_1', 'additional_id_2', 'additional_id']))
                    ->helperText('Pilih jenis ID untuk dokumen tambahan'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('User Photo')
            ->columns([
                Tables\Columns\ImageColumn::make('photo')
                    ->disk('public')
                    ->height(80)
                    ->width(80),
                    
                Tables\Columns\TextColumn::make('photo_type')
                    ->label('Tipe Foto')
                    ->formatStateUsing(function ($state) {
                        return match($state) {
                            'ktp' => '📄 KTP',
                            'additional_id_1' => '🆔 ID Tambahan 1',
                            'additional_id_2' => '🆔 ID Tambahan 2', 
                            'additional_id' => '🆔 ID Tambahan',
                            default => '📋 ' . $state
                        };
                    }),
                    
                Tables\Columns\TextColumn::make('id_type')
                    ->label('Jenis ID')
                    ->placeholder('Tidak diisi')
                    ->visible(fn($record) => in_array($record->photo_type ?? '', ['additional_id_1', 'additional_id_2', 'additional_id'])),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Diupload')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
