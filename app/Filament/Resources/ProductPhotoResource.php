<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductPhotoResource\Pages;
use App\Filament\Resources\ProductPhotoResource\RelationManagers;
use App\Models\ProductPhoto;
use App\Models\Product;
use App\Services\ProductPhotoImportExportService;
use Filament\Forms\Components;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\ImageColumn;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
                Action::make('import')
                    ->label('Import Product Photos')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('success')
                    ->form([
                        Components\FileUpload::make('file')
                            ->label('Excel File')
                            ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel', 'text/csv'])
                            ->required(),
                        Components\Checkbox::make('update_existing')
                            ->label('Update existing records')
                            ->helperText('If checked, existing photos will be updated. Otherwise, duplicates will be skipped.'),
                    ])
                    ->action(function (array $data) {
                        try {
                            $service = new ProductPhotoImportExportService();
                            $request = new Request();
                            $request->files->add(['file' => $data['file']]);
                            $request->merge(['update_existing' => $data['update_existing'] ?? false]);
                            
                            $result = $service->importProductPhotos($request);
                            
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
                    ->label('Export Product Photos')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('primary')
                    ->action(function () {
                        try {
                            $service = new ProductPhotoImportExportService();
                            $result = $service->exportProductPhotos();
                            
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
                            $service = new ProductPhotoImportExportService();
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
