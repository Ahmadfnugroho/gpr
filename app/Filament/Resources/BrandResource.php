<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BrandResource\Pages;
use App\Filament\Resources\BrandResource\RelationManagers;
use App\Models\Brand;
use App\Services\BrandImportExportService;
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
                Action::make('downloadTemplate')
                    ->label('Download Template')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->action(function () {
                        $service = new BrandImportExportService();
                        $filePath = $service->generateTemplate();
                        return response()->download($filePath, 'brand_import_template.xlsx')->deleteFileAfterSend();
                    }),
                    
                Action::make('import')
                    ->label('Import Excel')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('primary')
                    ->form([
                        FileUpload::make('excel_file')
                            ->label('Excel File')
                            ->acceptedFileTypes(['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'text/csv'])
                            ->required()
                            ->maxSize(2048)
                            ->helperText('Upload Excel file (.xls, .xlsx, .csv). Maximum 2MB'),
                        Checkbox::make('update_existing')
                            ->label('Update existing brands (based on name)')
                            ->default(false)
                            ->helperText('If unchecked, brands with existing names will be skipped')
                    ])
                    ->action(function (array $data) {
                        try {
                            $service = new BrandImportExportService();
                            $file = $data['excel_file'];
                            $updateExisting = $data['update_existing'] ?? false;
                            
                            // Convert to UploadedFile if needed
                            if (is_string($file)) {
                                $filePath = storage_path('app/public/' . $file);
                                $file = new \Illuminate\Http\UploadedFile(
                                    $filePath,
                                    basename($filePath),
                                    mime_content_type($filePath),
                                    null,
                                    true
                                );
                            }
                            
                            $results = $service->importBrands($file, $updateExisting);
                            
                            $message = "Import completed! Total: {$results['total']}, Success: {$results['success']}, Updated: {$results['updated']}, Failed: {$results['failed']}";
                            
                            if (!empty($results['errors'])) {
                                Notification::make()
                                    ->title('Import Completed with Errors')
                                    ->body($message . "\n\nErrors: " . implode(', ', array_slice($results['errors'], 0, 3)))
                                    ->warning()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('Import Successful')
                                    ->body($message)
                                    ->success()
                                    ->send();
                            }
                            
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Import Failed')
                                ->body('Error: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                    
                Action::make('export')
                    ->label('Export All')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('info')
                    ->action(function () {
                        $service = new BrandImportExportService();
                        $filePath = $service->exportBrands();
                        return response()->download($filePath, 'brands_export_' . date('Y-m-d_H-i-s') . '.xlsx')->deleteFileAfterSend();
                    }),
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
