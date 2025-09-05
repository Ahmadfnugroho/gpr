<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RentalIncludeResource\Pages;
use App\Filament\Resources\RentalIncludeResource\RelationManagers;
use App\Models\RentalInclude;
use App\Services\RentalIncludeImportExportService;
use Filament\Tables\Actions\ImportAction;
use Filament\Tables\Actions\ExportAction;
use App\Filament\Imports\RentalIncludeImporter;
use App\Filament\Exports\RentalIncludeExporter;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Rmsramos\Activitylog\Actions\ActivityLogTimelineTableAction;

class RentalIncludeResource extends Resource
{
    protected static ?string $model = RentalInclude::class;

    protected static ?string $navigationIcon = 'heroicon-o-inbox-stack';
    protected static ?string $navigationGroup = 'Product';

    protected static ?string $navigationLabel = 'Rental Includes';
    protected static ?int $navigationSort = 27;
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('product_id')
                    ->label('Produk')
                    ->relationship('product', 'name') // Relasi dengan produk yang di-include
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('include_product_id')
                    ->label('Produk yang Di-include')
                    ->relationship('includedProduct', 'name') // Relasi dengan produk yang di-include
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\TextInput::make('quantity')
                    ->label('Jumlah')
                    ->numeric()
                    ->required()

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultPaginationPageOption(50)
            ->headerActions([
                ImportAction::make()
                    ->importer(RentalIncludeImporter::class)
                    ->label('Import Rental Includes')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('primary')
                    ->options([
                        'updateExisting' => false,
                    ])
                    ->modalHeading('Import Rental Includes')
                    ->modalDescription('Upload an Excel file to import rental includes. Make sure your file has the correct format.')
                    ->modalSubmitActionLabel('Import')
                    ->successNotificationTitle('Rental Includes imported successfully'),
                    
                ExportAction::make()
                    ->exporter(RentalIncludeExporter::class)
                    ->label('Export Rental Includes')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->modalHeading('Export Rental Includes')
                    ->modalDescription('Export all rental includes to an Excel file.')
                    ->modalSubmitActionLabel('Export')
                    ->fileName(fn (): string => 'rental-includes-' . date('Y-m-d-H-i-s'))
                    ->successNotificationTitle('Rental Includes exported successfully'),
            ])

            ->columns([
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Produk Utama')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('includedProduct.name')
                    ->label('Produk yang Disertakan')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Jumlah')
                    ->numeric()
                    ->sortable(),
            ])
            ->filters([
                //
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
            'index' => Pages\ListRentalIncludes::route('/'),
            'create' => Pages\CreateRentalInclude::route('/create'),
            'edit' => Pages\EditRentalInclude::route('/{record}/edit'),
        ];
    }
}
