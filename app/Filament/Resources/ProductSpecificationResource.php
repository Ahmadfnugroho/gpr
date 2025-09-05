<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductSpecificationResource\Pages;
use App\Filament\Resources\ProductSpecificationResource\RelationManagers;
use App\Services\ProductSpecificationImportExportService;
use App\Models\ProductSpecification;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Checkbox;
use Filament\Notifications\Notification;
use Rmsramos\Activitylog\Actions\ActivityLogTimelineTableAction;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ProductSpecificationResource extends Resource
{
    protected static ?string $model = ProductSpecification::class;

    protected static ?string $navigationIcon = 'heroicon-o-swatch';

    protected static ?string $navigationGroup = 'Product';

    protected static ?string $navigationLabel = 'Product Specification';

    // protected static bool $shouldRegisterNavigation = false;

    protected static ?int $navigationSort = 25;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                forms\Components\Select::make('product_id')
                    ->label('Produk')
                    ->required()
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload(),

                forms\Components\MarkdownEditor::make('name')
                    ->required()
                    ->rules(['string']), // <- Hanya validasi tipe, tanpa batas panjang

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultPaginationPageOption(50)
            ->paginationPageOptions([25, 50, 100])
            ->defaultSort('updated_at', 'desc')
            ->striped()
            ->headerActions([
                Action::make('downloadTemplate')
                    ->label('Download Template')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->action(function () {
                        $service = new ProductSpecificationImportExportService();
                        $filePath = $service->generateTemplate();
                        return response()->download($filePath, 'product_specification_import_template.xlsx')->deleteFileAfterSend();
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
                            ->label('Update existing specifications (based on product_id and name)')
                            ->default(false)
                            ->helperText('If unchecked, specifications with existing combinations will be skipped')
                    ])
                    ->action(function (array $data) {
                        try {
                            $service = new ProductSpecificationImportExportService();
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

                            $results = $service->importProductSpecifications($file, $updateExisting);

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
                        $service = new ProductSpecificationImportExportService();
                        $filePath = $service->exportProductSpecifications();
                        return response()->download($filePath, 'product_specifications_export_' . date('Y-m-d_H-i-s') . '.xlsx')->deleteFileAfterSend();
                    }),
            ])
            ->columns([
                tables\Columns\TextColumn::make('product.name')
                    ->label('Produk')
                    ->searchable()
                    ->sortable()
                    ->limit(30)
                    ->tooltip(function (tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 30 ? $state : null;
                    }),
                
                tables\Columns\TextColumn::make('name')
                    ->label('Spesifikasi')
                    ->searchable()
                    ->sortable()
                    ->limit(100)
                    ->wrap()
                    ->tooltip(function (tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 100 ? $state : null;
                    })
                    ->description(function ($record) {
                        // Show first 100 characters as description if content is longer
                        $content = strip_tags($record->name);
                        return strlen($content) > 100 ? Str::limit($content, 100) : null;
                    }),
                    
                tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),
                    
                tables\Columns\TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                tables\Filters\SelectFilter::make('product_id')
                    ->label('Produk')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->multiple()
                    ->preload(),
                    
                tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Dibuat dari tanggal'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Dibuat sampai tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['created_from'] ?? null) {
                            $indicators[] = 'Dibuat dari ' . Carbon::parse($data['created_from'])->toFormattedDateString();
                        }
                        if ($data['created_until'] ?? null) {
                            $indicators[] = 'Dibuat sampai ' . Carbon::parse($data['created_until'])->toFormattedDateString();
                        }
                        return $indicators;
                    }),

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

                    Tables\Actions\BulkAction::make('exportSelected')
                        ->label('Export Selected')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('info')
                        ->action(function ($records) {
                            $service = new ProductSpecificationImportExportService();
                            $specificationIds = $records->pluck('id')->toArray();
                            $filePath = $service->exportProductSpecifications($specificationIds);
                            return response()->download($filePath, 'product_specifications_selected_export_' . date('Y-m-d_H-i-s') . '.xlsx')->deleteFileAfterSend();
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
            'index' => Pages\ListProductSpecifications::route('/'),
            'create' => Pages\CreateProductSpecification::route('/create'),
            'edit' => Pages\EditProductSpecification::route('/{record}/edit'),
        ];
    }
}
