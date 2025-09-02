<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Actions\ViewAction;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Illuminate\Support\Facades\Storage;

class CustomerPhotosRelationManager extends RelationManager
{
    protected static string $relationship = 'customerPhotos';

    protected static ?string $title = 'Foto Identitas';

    protected static ?string $modelLabel = 'Foto';

    protected static ?string $pluralModelLabel = 'Foto-foto';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('photo_type')
                    ->label('Jenis Foto')
                    ->options([
                        'ktp' => 'KTP',
                        'additional_id_1' => 'ID Tambahan 1',
                        'additional_id_2' => 'ID Tambahan 2',
                    ])
                    ->required(),
                    
                TextInput::make('id_type')
                    ->label('Jenis ID')
                    ->placeholder('KK, SIM, NPWP, STNK, BPKB, Passport, BPJS, ID_Kerja')
                    ->visible(fn (Forms\Get $get) => in_array($get('photo_type'), ['additional_id_1', 'additional_id_2'])),
                    
                FileUpload::make('photo')
                    ->label('Upload Foto')
                    ->image()
                    ->directory('customer_photos')
                    ->disk('public')
                    ->imageResizeMode('force')
                    ->imageCropAspectRatio(null)
                    ->imageResizeTargetWidth('1920')
                    ->imageResizeTargetHeight('1080')
                    ->maxSize(10240) // 10MB
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/webp'])
                    ->required()
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('photo_type')
            ->columns([
                TextColumn::make('photo_type')
                    ->label('Jenis Foto')
                    ->formatStateUsing(function (string $state): string {
                        return match($state) {
                            'ktp' => 'KTP',
                            'additional_id_1' => 'ID Tambahan 1',
                            'additional_id_2' => 'ID Tambahan 2',
                            default => ucfirst($state)
                        };
                    })
                    ->badge()
                    ->color(fn (string $state): string => match($state) {
                        'ktp' => 'primary',
                        'additional_id_1' => 'success',
                        'additional_id_2' => 'warning',
                        default => 'gray'
                    }),
                    
                TextColumn::make('id_type')
                    ->label('Tipe ID')
                    ->placeholder('N/A')
                    ->badge()
                    ->color('info'),
                    
                ImageColumn::make('photo')
                    ->label('Preview')
                    ->disk('public')
                    ->width(80)
                    ->height(60)
                    ->extraAttributes(['class' => 'rounded-lg'])
                    ->defaultImageUrl(url('/images/no-image.svg')),
                    
                TextColumn::make('created_at')
                    ->label('Tanggal Upload')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('photo_type')
                    ->label('Jenis Foto')
                    ->options([
                        'ktp' => 'KTP',
                        'additional_id_1' => 'ID Tambahan 1',
                        'additional_id_2' => 'ID Tambahan 2',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Tambah Foto')
                    ->icon('heroicon-o-camera')
                    ->successRedirectUrl(fn () => $this->getOwnerRecord()->resource::getUrl('view', ['record' => $this->getOwnerRecord()])),
            ])
            ->actions([
                Tables\Actions\Action::make('view_large')
                    ->label('Lihat Foto Besar')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading(fn ($record) => 'Foto ' . match($record->photo_type) {
                        'ktp' => 'KTP',
                        'additional_id_1' => 'ID Tambahan 1', 
                        'additional_id_2' => 'ID Tambahan 2',
                        default => ucfirst($record->photo_type)
                    })
                    ->modalContent(function ($record) {
                        $photoUrl = Storage::disk('public')->url($record->photo);
                        return view('filament.components.large-image-modal', [
                            'imageUrl' => $photoUrl,
                            'title' => match($record->photo_type) {
                                'ktp' => 'KTP',
                                'additional_id_1' => 'ID Tambahan 1',
                                'additional_id_2' => 'ID Tambahan 2', 
                                default => ucfirst($record->photo_type)
                            },
                            'idType' => $record->id_type
                        ]);
                    })
                    ->modalWidth('7xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup'),
                    
                Tables\Actions\EditAction::make()
                    ->successRedirectUrl(fn () => $this->getOwnerRecord()->resource::getUrl('view', ['record' => $this->getOwnerRecord()])),
                    
                Tables\Actions\DeleteAction::make()
                    ->successRedirectUrl(fn () => $this->getOwnerRecord()->resource::getUrl('view', ['record' => $this->getOwnerRecord()])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('Belum ada foto')
            ->emptyStateDescription('Tambahkan foto identitas customer di sini')
            ->emptyStateIcon('heroicon-o-camera');
    }
}
