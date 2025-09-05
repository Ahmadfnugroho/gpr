<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BrandResource\Pages;
use App\Filament\Resources\BrandResource\RelationManagers;
use App\Models\Brand;
use App\Services\BrandImportExportService;
use Filament\Tables\Actions\ImportAction;
use Filament\Tables\Actions\ExportAction;
use App\Filament\Imports\BrandImporter;
use App\Filament\Exports\BrandExporter;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\HeaderActions;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Checkbox;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Columns\ImageColumn;
use Rmsramos\Activitylog\Actions\ActivityLogTimelineTableAction;

class BrandResource extends Resource
{
    protected static ?string $model = Brand::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationGroup = 'Product';

    protected static ?string $navigationLabel = 'Brands';

    // protected static bool $shouldRegisterNavigation = false;

    protected static ?int $navigationSort = 24;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                forms\Components\TextInput::make('name')
                    ->label('Nama Brand')
                    ->required()
                    ->maxLength(255),
                forms\Components\FileUpload::make('logo')
                    ->label('Logo')
                    ->nullable()
                    ->image(),
                Forms\Components\Toggle::make('premiere')
                    ->label('Brand Premiere')
                    ->default(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultPaginationPageOption(50)
            ->headerActions([
                ImportAction::make()
                    ->importer(BrandImporter::class)
                    ->label('Import Brands')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('primary')
                    ->options([
                        'updateExisting' => false,
                    ])
                    ->modalHeading('Import Brands')
                    ->modalDescription('Upload an Excel file to import brands. Make sure your file has the correct format.')
                    ->modalSubmitActionLabel('Import')
                    ->successNotificationTitle('Brands imported successfully'),
                    
                ExportAction::make()
                    ->exporter(BrandExporter::class)
                    ->label('Export Brands')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->modalHeading('Export Brands')
                    ->modalDescription('Export all brands to an Excel file.')
                    ->modalSubmitActionLabel('Export')
                    ->fileName(fn (): string => 'brands-' . date('Y-m-d-H-i-s'))
                    ->successNotificationTitle('Brands exported successfully'),
            ])

            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\ToggleColumn::make('premiere')
                    ->label('Brand Premiere'),
                ImageColumn::make('logo')
                    ->label('logo')
                    ->getStateUsing(fn($record) => asset('storage/' . $record->logo)),

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
                    ])
                    ->limit(10),

            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('enable_premiere')
                        ->icon('heroicon-o-star')
                        ->color('warning')
                        ->label('Aktifkan Brand Premiere')
                        ->requiresConfirmation()
                        ->modalHeading('Aktifkan Brand Premiere')
                        ->modalDescription('Apakah Anda yakin ingin mengaktifkan brand premiere untuk brand yang dipilih?')
                        ->modalSubmitActionLabel('Ya, Aktifkan')
                        ->action(function ($records) {
                            $count = $records->count();
                            $records->each(function ($record) {
                                $record->update(['premiere' => true]);
                            });
                            Notification::make()
                                ->success()
                                ->title('Berhasil Mengaktifkan Brand Premiere')
                                ->body("{$count} brand berhasil diaktifkan sebagai premiere.")
                                ->send();
                        }),
                    Tables\Actions\BulkAction::make('disable_premiere')
                        ->icon('heroicon-o-star')
                        ->color('gray')
                        ->label('Nonaktifkan Brand Premiere')
                        ->requiresConfirmation()
                        ->modalHeading('Nonaktifkan Brand Premiere')
                        ->modalDescription('Apakah Anda yakin ingin menonaktifkan brand premiere untuk brand yang dipilih?')
                        ->modalSubmitActionLabel('Ya, Nonaktifkan')
                        ->action(function ($records) {
                            $count = $records->count();
                            $records->each(function ($record) {
                                $record->update(['premiere' => false]);
                            });
                            Notification::make()
                                ->success()
                                ->title('Berhasil Menonaktifkan Brand Premiere')
                                ->body("{$count} brand berhasil dinonaktifkan dari premiere.")
                                ->send();
                        }),
                    Tables\Actions\DeleteBulkAction::make(),
                    
                    Action::make('exportSelected')
                        ->label('Export Selected')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('info')
                        ->action(function ($records) {
                            $service = new BrandImportExportService();
                            $brandIds = $records->pluck('id')->toArray();
                            $filePath = $service->exportBrands($brandIds);
                            return response()->download($filePath, 'brands_selected_export_' . date('Y-m-d_H-i-s') . '.xlsx')->deleteFileAfterSend();
                        }),
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
            'index' => Pages\ListBrands::route('/'),
            'create' => Pages\CreateBrand::route('/create'),
            'edit' => Pages\EditBrand::route('/{record}/edit'),
        ];
    }
}
