<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use App\Models\ProductPhoto;
use App\Filament\Imports\ProductPhotoImporter;
use Filament\Tables\Actions\ImportAction;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use \Illuminate\Database\Eloquent\Model;

class ProductPhotoRelationManager extends RelationManager
{
    protected static string $relationship = 'ProductPhotos';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\FIleUpload::make('photo')
                    ->required()
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->headerActions([
                Tables\Actions\CreateAction::make(),
                ImportAction::make()
                    ->importer(ProductPhotoImporter::class),
            ])
            ->recordTitleAttribute('product_id')
            ->columns([
                ImageColumn::make('photo')
                    ->label('Photo')
                    ->getStateUsing(fn($record) => asset('storage/' . $record->photo)),
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
