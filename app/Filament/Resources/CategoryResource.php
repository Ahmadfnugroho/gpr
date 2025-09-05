<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use Filament\Tables\Table;
use App\Models\Category;
use App\Services\CategoryImportExportService;
use Filament\Actions\ImportAction;
use Filament\Actions\ExportAction;
use App\Filament\Imports\CategoryImporter;
use App\Filament\Exports\CategoryExporter;
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
                ImportAction::make()
                    ->importer(CategoryImporter::class)
                    ->label('Import Categories')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('primary')
                    ->options([
                        'updateExisting' => false,
                    ])
                    ->modalHeading('Import Categories')
                    ->modalDescription('Upload an Excel file to import categories. Make sure your file has the correct format.')
                    ->modalSubmitActionLabel('Import')
                    ->successNotificationTitle('Categories imported successfully'),
                    
                ExportAction::make()
                    ->exporter(CategoryExporter::class)
                    ->label('Export Categories')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->modalHeading('Export Categories')
                    ->modalDescription('Export all categories to an Excel file.')
                    ->modalSubmitActionLabel('Export')
                    ->fileName(fn (): string => 'categories-' . date('Y-m-d-H-i-s'))
                    ->successNotificationTitle('Categories exported successfully'),
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
