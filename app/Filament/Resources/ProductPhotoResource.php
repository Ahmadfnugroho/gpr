<?php

namespace App\Filament\Resources;

use App\Filament\Exports\ProductPhotoExporter;
use App\Filament\Imports\ProductPhotoImporter;
use App\Filament\Resources\ProductPhotoResource\Pages;
use App\Filament\Resources\ProductPhotoResource\RelationManagers;
use App\Models\ProductPhoto;
use App\Models\Product;
use Filament\Forms\Components;
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

class ProductPhotoResource extends Resource
{
    protected static ?string $model = ProductPhoto::class;

    protected static ?string $navigationIcon = 'heroicon-o-photo';

    protected static ?string $navigationGroup = 'Product';

    protected static ?string $navigationLabel = 'Photo product';

    // protected static bool $shouldRegisterNavigation = false;

    protected static ?int $navigationSort = 26;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                \Filament\Forms\Components\FileUpload::make('photo')
                    ->label('Foto Produk')
                    ->image()
                    ->required(),
                Components\Select::make('product_id')
                    ->label('Produk')
                    ->relationship('product', 'name')
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
                    ->importer(ProductPhotoImporter::class),
                ExportAction::make()
                    ->exporter(ProductPhotoExporter::class),
            ])

            ->columns([
                Tables\Columns\TextColumn::make('product.name')
                    ->searchable()
                    ->sortable(),



                ImageColumn::make('photo')
                    ->label('Photo')
                    ->getStateUsing(fn($record) => asset('storage/' . $record->photo))
                    ->size(640) // ukuran dalam piksel (default biasanya 40)

            ])
            ->filters([
                Tables\Filters\SelectFilter::make('product.name')
                    ->searchable()
                    ->multiple()
                    ->preload(),

            ])
            ->actions([
                Tables\Actions\Action::make('view_full_photo')
                    ->label('Lihat Besar')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading(fn($record) => 'Foto Produk - ' . $record->product->name)
                    ->modalContent(fn($record) => view('filament.resources.product-photo-resource.pages.view-full-photo', ['productPhoto' => $record]))
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
            'index' => Pages\ListProductPhotos::route('/'),
            'create' => Pages\CreateProductPhoto::route('/create'),
            'edit' => Pages\EditProductPhoto::route('/{record}/edit'),
        ];
    }
}
