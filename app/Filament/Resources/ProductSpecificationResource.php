<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductSpecificationResource\Pages;
use App\Filament\Resources\ProductSpecificationResource\RelationManagers;
use App\Services\ProductSpecificationImportExportService;
use Filament\Tables\Actions\ImportAction;
use Filament\Tables\Actions\ExportAction;
use App\Filament\Imports\ProductSpecificationImporter;
use App\Filament\Exports\ProductSpecificationExporter;
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
                ImportAction::make()
                    ->importer(ProductSpecificationImporter::class)
                    ->label('Import Product Specifications')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('primary')
                    ->options([
                        'updateExisting' => false,
                    ])
                    ->modalHeading('Import Product Specifications')
                    ->modalDescription('Upload an Excel file to import product specifications. Make sure your file has the correct format.')
                    ->modalSubmitActionLabel('Import')
                    ->successNotificationTitle('Product Specifications imported successfully'),
                    
                ExportAction::make()
                    ->exporter(ProductSpecificationExporter::class)
                    ->label('Export Product Specifications')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->modalHeading('Export Product Specifications')
                    ->modalDescription('Export all product specifications to an Excel file.')
                    ->modalSubmitActionLabel('Export')
                    ->fileName(fn (): string => 'product-specifications-' . date('Y-m-d-H-i-s'))
                    ->successNotificationTitle('Product Specifications exported successfully'),
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
