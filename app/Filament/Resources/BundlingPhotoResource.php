<?php

namespace App\Filament\Resources;

use App\Filament\Imports\BundlingPhotoImporter;
use App\Filament\Resources\BundlingPhotoResource\Pages;
use App\Filament\Resources\BundlingPhotoResource\RelationManagers;
use App\Models\BundlingPhoto;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\ImportAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Rmsramos\Activitylog\Actions\ActivityLogTimelineTableAction;

class BundlingPhotoResource extends Resource
{
    protected static ?string $model = BundlingPhoto::class;

    protected static ?string $navigationIcon = 'heroicon-o-photo';

    protected static ?string $navigationGroup = 'Product';

    protected static ?string $navigationLabel = 'Bundling Photo';

    // protected static bool $shouldRegisterNavigation = false;

    protected static ?int $navigationSort = 29;



    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                FileUpload::make('photo')
                    ->label('Foto Bundling')
                    ->image()
                    ->required(),
                Select::make('bundling_id')
                    ->label('Bundling')
                    ->relationship('bundling', 'name')
                    ->required()
                    ->searchable()
                    ->preload()

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultPaginationPageOption(50)
            ->headerActions([
                ImportAction::make()
                    ->importer(BundlingPhotoImporter::class)
                    ->label('Import Bundling Photo'),

            ])

            ->columns([
                Tables\Columns\TextColumn::make('bundling.name')
                    ->searchable()
                    ->sortable(),
                ImageColumn::make('photo')
                    ->label('Photo')
                    ->getStateUsing(fn($record) => asset('storage/' . $record->photo))
                    ->size(400) // ukuran dalam piksel (default biasanya 40)


            ])
            ->filters([
                Tables\Filters\SelectFilter::make('bundling.name')
                    ->searchable()
                    ->multiple()
                    ->preload(),

            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make(),
                ActivityLogTimelineTableAction::make('Activities')
                    ->timelineIcons([
                        'created' => 'heroicon-m-check-badge',
                        'updated' => 'heroicon-m-pencil-square',
                    ])
                    ->timelineIconColors([
                        'created' => 'info',
                        'updated' => 'warning',
                    ])
                    ->limit(10)
                    ->label('History')




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
            'index' => Pages\ListBundlingPhotos::route('/'),
            'create' => Pages\CreateBundlingPhoto::route('/create'),
            'edit' => Pages\EditBundlingPhoto::route('/{record}/edit'),
        ];
    }
}
