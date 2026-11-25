<?php

namespace App\Filament\Resources;

use App\Filament\Exports\BundlingPhotoExporter;
use App\Filament\Imports\BundlingPhotoImporter;
use App\Filament\Resources\BundlingPhotoResource\Pages;
use App\Filament\Resources\BundlingPhotoResource\RelationManagers;
use App\Models\BundlingPhoto;
use App\Services\ImageCompressionService;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\ExportAction;
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
                ImageCompressionService::apply(
                    FileUpload::make('photo')
                        ->label('Foto Bundling')
                        ->image()
                        ->required(),
                    'bundling-photos',
                    1200,
                ),
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
                    ->importer(BundlingPhotoImporter::class),
                ExportAction::make()
                    ->exporter(BundlingPhotoExporter::class),
            ])

            ->columns([
                Tables\Columns\TextColumn::make('bundling.name')
                    ->searchable()
                    ->sortable(),
                ImageColumn::make('photo')
                    ->label('Photo')
                    ->disk('media')
                    ->defaultImageUrl(url('/images/no-image.svg'))
                    ->size(400)


            ])
            ->filters([
                Tables\Filters\SelectFilter::make('bundling.name')
                    ->searchable()
                    ->multiple()
                    ->preload(),

            ])
            ->actions([
                Tables\Actions\Action::make('view_full_photo')
                    ->label('Lihat Besar')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading(fn($record) => 'Foto Bundling - ' . $record->bundling->name)
                    ->modalContent(fn($record) => view('filament.resources.bundling-photo-resource.pages.view-full-photo', ['bundlingPhoto' => $record]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup'),
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
