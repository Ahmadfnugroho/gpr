<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerPhotoResource\Pages;
use App\Filament\Resources\CustomerPhotoResource\RelationManagers;
use App\Models\CustomerPhoto;
use App\Models\Customer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Rmsramos\Activitylog\Actions\ActivityLogTimelineTableAction;

class CustomerPhotoResource extends Resource
{
    protected static ?string $model = CustomerPhoto::class;

    protected static ?string $navigationIcon = 'heroicon-o-photo';
    protected static ?string $navigationGroup = 'Customer Management';
    protected static ?string $navigationLabel = 'Customer Photos';
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
                Forms\Components\Select::make('customer_id')
                    ->label('Customer')
                    ->required()
                    ->relationship('customer', 'name')
                    ->searchable(),
                Forms\Components\TextInput::make('id_type')
                    ->label('ID Type')
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultPaginationPageOption(50)
            ->columns([
                Tables\Columns\ImageColumn::make('photo')
                    ->label('Preview')
                    ->disk('public')
                    ->height(80)
                    ->width(80)
                    ->defaultImageUrl('/images/placeholder.png'),
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Customer')
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
                Tables\Columns\TextColumn::make('id_type')
                    ->label('ID Type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Diupload')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('customer.name')
                    ->searchable()
                    ->multiple()
                    ->preload(),
                Tables\Filters\SelectFilter::make('photo_type')
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
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('view_full_photo')
                    ->label('Lihat Besar')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading(fn($record) => 'Foto ' . ($record->photo_type ?: 'Dokumen') . ' - ' . $record->customer->name)
                    ->modalContent(fn($record) => view('filament.resources.customer-photo-resource.pages.view-full-photo', ['customerPhoto' => $record]))
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
            'index' => Pages\ListCustomerPhotos::route('/'),
            'create' => Pages\CreateCustomerPhoto::route('/create'),
            'edit' => Pages\EditCustomerPhoto::route('/{record}/edit'),
        ];
    }
}
