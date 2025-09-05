<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RentalIncludeResource\Pages;
use App\Filament\Resources\RentalIncludeResource\RelationManagers;
use App\Models\RentalInclude;
use App\Services\RentalIncludeImportExportService;
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
                Action::make('import')
                    ->label('Import Rental Includes')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('success')
                    ->form([
                        Forms\Components\FileUpload::make('file')
                            ->label('Excel File')
                            ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel', 'text/csv'])
                            ->required(),
                        Forms\Components\Checkbox::make('update_existing')
                            ->label('Update existing records')
                            ->helperText('If checked, existing rental includes will be updated. Otherwise, duplicates will be skipped.'),
                    ])
                    ->action(function (array $data) {
                        try {
                            $service = new RentalIncludeImportExportService();
                            $request = new Request();
                            $request->files->add(['file' => $data['file']]);
                            $request->merge(['update_existing' => $data['update_existing'] ?? false]);
                            
                            $result = $service->importRentalIncludes($request);
                            
                            $message = "Import completed! ";
                            $message .= "Success: {$result['results']['success']}, ";
                            $message .= "Failed: {$result['results']['failed']}, ";
                            $message .= "Updated: {$result['results']['updated']}";
                            
                            if (!empty($result['results']['errors'])) {
                                $errorDetails = implode("\n", array_slice($result['results']['errors'], 0, 10));
                                if (count($result['results']['errors']) > 10) {
                                    $errorDetails .= "\n... and " . (count($result['results']['errors']) - 10) . " more errors";
                                }
                                
                                Notification::make()
                                    ->title('Import completed with errors')
                                    ->body($message . "\n\nFirst few errors:\n" . $errorDetails)
                                    ->warning()
                                    ->persistent()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('Import successful!')
                                    ->body($message)
                                    ->success()
                                    ->send();
                            }
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Import failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                    
                Action::make('export')
                    ->label('Export Rental Includes')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('primary')
                    ->action(function () {
                        try {
                            $service = new RentalIncludeImportExportService();
                            $result = $service->exportRentalIncludes();
                            
                            return response()->download(Storage::path($result['filepath']), $result['filename']);
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Export failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                    
                Action::make('download_template')
                    ->label('Download Template')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('gray')
                    ->action(function () {
                        try {
                            $service = new RentalIncludeImportExportService();
                            $result = $service->downloadTemplate();
                            
                            return response()->download(Storage::path($result['filepath']), $result['filename']);
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Template download failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
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
