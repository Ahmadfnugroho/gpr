<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use Filament\Tables\Table;
use App\Models\Category;
use App\Services\CategoryImportExportService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\HeaderActions;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Checkbox;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Columns\ImageColumn;
use Rmsramos\Activitylog\Actions\ActivityLogTimelineTableAction;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $navigationIcon = 'heroicon-o-folder';

    protected static ?string $navigationGroup = 'Product';

    protected static ?string $navigationLabel = 'Categories';

    // protected static bool $shouldRegisterNavigation = false;

    protected static ?int $navigationSort = 22;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nama Kategori')
                    ->required()
                    ->maxLength(255),

                Forms\Components\FileUpload::make('photo')
                    ->disk('public')
                    ->directory('categories')
                    ->visibility('public')
                    ->maxSize(1024) // optional: 1MB
                    ->label('Photo')
                    ->required()
                    ->image(),
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
                        $service = new CategoryImportExportService();
                        $filePath = $service->generateTemplate();
                        return response()->download($filePath, 'category_import_template.xlsx')->deleteFileAfterSend();
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
                            ->label('Update existing categories (based on name)')
                            ->default(false)
                            ->helperText('If unchecked, categories with existing names will be skipped')
                    ])
                    ->action(function (array $data) {
                        try {
                            $service = new CategoryImportExportService();
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
                            
                            $results = $service->importCategories($file, $updateExisting);
                            
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
                        $service = new CategoryImportExportService();
                        $filePath = $service->exportCategories();
                        return response()->download($filePath, 'categories_export_' . date('Y-m-d_H-i-s') . '.xlsx')->deleteFileAfterSend();
                    }),
            ])
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Kategori')
                    ->searchable()
                    ->sortable(),
                ImageColumn::make('photo')
                    ->label('Photo')
                    ->getStateUsing(fn($record) => asset('storage/' . $record->photo)),

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
                    Tables\Actions\DeleteBulkAction::make(),
                    
                    Action::make('exportSelected')
                        ->label('Export Selected')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('info')
                        ->action(function ($records) {
                            $service = new CategoryImportExportService();
                            $categoryIds = $records->pluck('id')->toArray();
                            $filePath = $service->exportCategories($categoryIds);
                            return response()->download($filePath, 'categories_selected_export_' . date('Y-m-d_H-i-s') . '.xlsx')->deleteFileAfterSend();
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
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }
}
