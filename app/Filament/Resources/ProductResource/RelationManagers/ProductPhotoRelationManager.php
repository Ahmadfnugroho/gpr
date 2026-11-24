<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\FileUpload;
use App\Services\ImageCompressionService;
use Filament\Resources\RelationManagers\RelationManager;
use App\Filament\Imports\ProductPhotoImporter;
use Filament\Tables\Actions\ImportAction;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

class ProductPhotoRelationManager extends RelationManager
{
    protected static string $relationship = 'productPhotos';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Upload Options')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Multiple Photos')
                            ->schema([
                                FileUpload::make('photos')
                                    ->label('Upload Multiple Photos')
                                    ->image()
                                    ->multiple()
                                    ->directory('product-photos')
                                    ->visibility('public')
                                    ->imageEditor()
                                    ->imageEditorAspectRatios(['16:9', '4:3', '1:1'])
                                    ->imageCropAspectRatio('16:9')
                                    ->imageResizeTargetWidth('1920')
                                    ->imageResizeTargetHeight('1080')
                                    ->maxSize(10240) // 10MB per file (will be compressed)
                                    ->maxFiles(10) // Maximum 10 files
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                    ->helperText('Upload multiple photos at once. Maximum 10 files, 10MB each. Photos will be compressed automatically.')
                                    ->saveUploadedFileUsing(function ($file) {
                                        $compressionService = new ImageCompressionService();
                                        return $compressionService->compressAndStore($file, 'product-photos');
                                    })
                                    ->required(),
                            ]),
                    ])
                    ->columnSpanFull(),

                Forms\Components\Placeholder::make('existing_photos_info')
                    ->label('Current Photos')
                    ->content(function () {
                        $product = $this->ownerRecord;
                        if (!$product) return 'Product not found';

                        $photosCount = $product->productPhotos()->count();
                        return "This product currently has {$photosCount} photo(s) in the gallery.";
                    })
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),

                ImageColumn::make('photo')
                    ->label('Photo Preview')
                    ->disk('public')
                    ->size(80)
                    ->square()
                    ->defaultImageUrl(url('/images/no-image.svg')),

                Tables\Columns\TextColumn::make('photo')
                    ->label('File Name')
                    ->searchable()
                    ->formatStateUsing(function ($state) {
                        return $state ? basename($state) : '-';
                    })
                    ->tooltip(function ($state) {
                        return $state ? 'Full path: ' . $state : 'No file';
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Uploaded')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Upload Photos')
                    ->icon('heroicon-o-photo')
                    ->modalHeading('Upload Product Photos')
                    ->modalDescription('Upload multiple photos for this product. Recommended size: 1920x1080px')
                    ->modalSubmitActionLabel('Upload Photos')
                    ->using(function (array $data, string $model): Model {
                        $photos = array_filter($data['photos'] ?? []);

                        if (empty($photos)) {
                            throw new \RuntimeException('No photos were uploaded.');
                        }

                        $first = null;
                        foreach ($photos as $photo) {
                            $first ??= $model::create([
                                'product_id' => $this->ownerRecord->id,
                                'photo' => $photo,
                            ]);
                        }

                        return $first;
                    })
                    ->successNotificationTitle(
                        fn (): string => 'Foto berhasil diunggah ke galeri produk.'
                    ),

                ImportAction::make()
                    ->importer(ProductPhotoImporter::class)
                    ->label('Import Photos')
                    ->icon('heroicon-o-arrow-up-tray'),
            ])
            ->actions([
                // View Large Photo Action
                Action::make('viewLarge')
                    ->label('View Large')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading(fn($record) => 'Product Photo #' . $record->id)
                    ->modalContent(function ($record) {
                        if (!$record->photo) {
                            return new HtmlString('<p class="text-center text-gray-500">No photo available</p>');
                        }

                        $imageUrl = e(asset('storage/' . $record->photo));
                        $fileName = e(basename($record->photo));
                        $productName = e($record->product->name ?? 'Unknown');

                        return new HtmlString('
                            <div class="text-center">
                                <img src="' . $imageUrl . '" 
                                     alt="Product Photo" 
                                     class="max-w-full h-auto rounded-lg shadow-lg mx-auto" 
                                     style="max-height: 70vh;" />
                                <div class="mt-4 p-4 bg-gray-50 rounded-lg">
                                    <h3 class="text-lg font-semibold mb-2">Photo Details</h3>
                                    <div class="grid grid-cols-2 gap-4 text-sm">
                                        <div>
                                            <span class="font-medium text-gray-600">File Name:</span><br>
                                            <span class="text-gray-800">' . $fileName . '</span>
                                        </div>
                                        <div>
                                            <span class="font-medium text-gray-600">Uploaded:</span><br>
                                            <span class="text-gray-800">' . $record->created_at->format('d M Y, H:i') . '</span>
                                        </div>
                                        <div>
                                            <span class="font-medium text-gray-600">Product:</span><br>
                                            <span class="text-gray-800">' . $productName . '</span>
                                        </div>
                                        <div>
                                            <span class="font-medium text-gray-600">Direct URL:</span><br>
                                            <a href="' . $imageUrl . '" target="_blank" class="text-blue-600 hover:text-blue-800 underline">
                                                Open in new tab
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        ');
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->slideOver()
                    ->visible(fn($record) => (bool) $record->photo),

                Tables\Actions\EditAction::make()
                    ->label('Edit')
                    ->icon('heroicon-o-pencil')
                    ->modalHeading('Edit Product Photo')
                    ->modalSubmitActionLabel('Update'),

                Tables\Actions\DeleteAction::make()
                    ->label('Delete')
                    ->icon('heroicon-o-trash')
                    ->modalHeading('Delete Product Photo')
                    ->modalDescription('Are you sure you want to delete this photo? This action cannot be undone.')
                    ->modalSubmitActionLabel('Delete Photo'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Delete Selected')
                        ->modalHeading('Delete Selected Photos')
                        ->modalDescription('Are you sure you want to delete the selected photos? This action cannot be undone.')
                        ->modalSubmitActionLabel('Delete Photos'),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50]);
    }
}
