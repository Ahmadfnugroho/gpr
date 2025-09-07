<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductAvailabilityResource\Pages;
use App\Models\ProductAvailability;
use Carbon\Carbon;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Grid;
use Filament\Support\Enums\FontWeight;
use Illuminate\Support\HtmlString;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Collection;
use App\Models\Product;
use App\Models\Bundling;
use App\Models\Transaction;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Forms\Components\Actions;
use Illuminate\Support\Facades\DB;

class ProductAvailabilityResource extends Resource
{
    protected static ?string $model = ProductAvailability::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationGroup = 'Inventory Management';
    protected static ?string $navigationLabel = 'Product Availability';
    protected static ?int $navigationSort = 25;
    protected static ?string $slug = 'product-availability';

    // Disable create, edit, delete since this is read-only
    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('🔍 Product & Bundling Availability Search')
                    ->description('Select products/bundlings and set date range to check their availability')
                    ->schema([
                        Grid::make(1)
                            ->schema([
                                Select::make('selected_items')
                                    ->label('🛍️📦 Select Products/Bundlings')
                                    ->multiple()
                                    ->searchable()
                                    ->options(function () {
                                        $products = Product::where('status', '!=', 'deleted')
                                            ->orderBy('name')
                                            ->pluck('name', 'id')
                                            ->mapWithKeys(function($name, $id) {
                                                return ["product-{$id}" => "🛍️ {$name}"];
                                            });
                                            
                                        $bundlings = Bundling::orderBy('name')
                                            ->pluck('name', 'id')
                                            ->mapWithKeys(function($name, $id) {
                                                return ["bundling-{$id}" => "📦 {$name}"];
                                            });
                                            
                                        return $products->merge($bundlings)->toArray();
                                    })
                                    ->placeholder('Type to search products or bundlings...')
                                    ->helperText('💡 Select one or more items to check availability')
                                    ->columnSpanFull(),
                            ]),
                            
                        Grid::make(2)
                            ->schema([
                                DateTimePicker::make('start_date')
                                    ->label('📅 Start Date & Time')
                                    ->default(request('start_date', now()))
                                    ->native(false)
                                    ->displayFormat('d M Y H:i')
                                    ->helperText('Start of availability check period')
                                    ->required(),
                                    
                                DateTimePicker::make('end_date')
                                    ->label('📅 End Date & Time')
                                    ->default(request('end_date', now()->addDays(7)->endOfDay()))
                                    ->after('start_date')
                                    ->native(false)
                                    ->displayFormat('d M Y H:i')
                                    ->helperText('End of availability check period')
                                    ->required(),
                            ]),
                            
                        Actions::make([
                            FormAction::make('search')
                                ->label('🔍 Search Availability')
                                ->icon('heroicon-o-magnifying-glass')
                                ->color('primary')
                                ->size('lg')
                                ->action('searchAction'),
                                
                            FormAction::make('clear')
                                ->label('🔄 Clear Filters')
                                ->icon('heroicon-o-arrow-path')
                                ->color('gray')
                                ->url(route('filament.admin.resources.product-availability.index')),
                        ])
                        ->alignment('center')
                        ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->persistCollapsed(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultPaginationPageOption(50)
            ->modifyQueryUsing(function (Builder $query) {
                // Since we're using a virtual model, we'll handle the data differently
                // This query won't be used, but we need to return something
                return $query->whereRaw('1 = 0'); // Return empty initially
            })
            ->columns([
                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'product' => 'primary',
                        'bundling' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => ucfirst($state)),

                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::SemiBold)
                    ->wrap()
                    ->description(function ($record) {
                        try {
                            return $record->getDescription();
                        } catch (\Exception $e) {
                            return 'Description unavailable';
                        }
                    }),

                TextColumn::make('total_items')
                    ->label('Total Items')
                    ->getStateUsing(function ($record) {
                        try {
                            return $record->getTotalItems();
                        } catch (\Exception $e) {
                            return 0;
                        }
                    })
                    ->alignCenter()
                    ->sortable(false),

                TextColumn::make('available_items')
                    ->label('Available Items')
                    ->getStateUsing(function ($record) {
                        try {
                            $startDate = request('tableFilters.date_range.start_date');
                            $endDate = request('tableFilters.date_range.end_date');

                            if (!$startDate || !$endDate) {
                                $startDate = now()->format('Y-m-d');
                                $endDate = now()->addDays(7)->format('Y-m-d');
                            }

                            return $record->getAvailableItemsForPeriod($startDate, $endDate);
                        } catch (\Exception $e) {
                            return 0;
                        }
                    })
                    ->alignCenter()
                    ->color(function ($state, $record) {
                        $total = $record->getTotalItems();
                        if ($total == 0) return 'gray';
                        $percentage = ($state / $total) * 100;
                        if ($percentage >= 70) return 'success';
                        if ($percentage >= 30) return 'warning';
                        return 'danger';
                    })
                    ->weight(FontWeight::Bold),

                TextColumn::make('availability_percentage')
                    ->label('Availability %')
                    ->getStateUsing(function ($record) {
                        $startDate = request('tableFilters.date_range.start_date');
                        $endDate = request('tableFilters.date_range.end_date');

                        if (!$startDate || !$endDate) {
                            $startDate = now()->format('Y-m-d');
                            $endDate = now()->addDays(7)->format('Y-m-d');
                        }

                        $available = $record->getAvailableItemsForPeriod($startDate, $endDate);
                        $total = $record->getTotalItems();

                        if ($total == 0) return '0%';
                        $percentage = round(($available / $total) * 100);
                        return "{$percentage}%";
                    })
                    ->alignCenter()
                    ->color(function ($state) {
                        $percentage = (int) str_replace('%', '', $state);
                        if ($percentage >= 70) return 'success';
                        if ($percentage >= 30) return 'warning';
                        return 'danger';
                    })
                    ->weight(FontWeight::Bold),

                TextColumn::make('rental_periods')
                    ->label('Current Rentals')
                    ->getStateUsing(function ($record) {
                        try {
                            $startDate = request('tableFilters.date_range.start_date');
                            $endDate = request('tableFilters.date_range.end_date');

                            if (!$startDate || !$endDate) {
                                $startDate = now()->format('Y-m-d');
                                $endDate = now()->addDays(7)->format('Y-m-d');
                            }

                            return $record->getActiveRentalPeriods($startDate, $endDate);
                        } catch (\Exception $e) {
                            return 'No active rentals';
                        }
                    })
                    ->html()
                    ->wrap()
                    ->size(TextColumn\TextColumnSize::Small),
            ])
            ->filters([
                Filter::make('item_selection')
                    ->form([
                        Select::make('selected_items')
                            ->label('🛍️📦 Select Products/Bundlings')
                            ->multiple()
                            ->searchable()
                            ->options(function () {
                                $products = Product::where('status', '!=', 'deleted')
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->mapWithKeys(function ($name, $id) {
                                        return ["product-{$id}" => "🛍️ {$name}"];
                                    });

                                $bundlings = Bundling::orderBy('name')
                                    ->pluck('name', 'id')
                                    ->mapWithKeys(function ($name, $id) {
                                        return ["bundling-{$id}" => "📦 {$name}"];
                                    });

                                return $products->merge($bundlings)->toArray();
                            })
                            ->placeholder('Type to search products or bundlings...')
                            ->helperText('💡 Leave empty to show all items'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        // For virtual model, filtering is handled in the page
                        return $query;
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (empty($data['selected_items'])) {
                            return null;
                        }
                        $count = count($data['selected_items']);
                        return "Selected: {$count} items";
                    }),

                Filter::make('date_range')
                    ->form([
                        Grid::make(2)
                            ->schema([
                                DateTimePicker::make('start_date')
                                    ->label('📅 Start Date & Time')
                                    ->default(now())
                                    ->displayFormat('d M Y H:i')
                                    ->native(false)
                                    ->required(),
                                DateTimePicker::make('end_date')
                                    ->label('📅 End Date & Time')
                                    ->default(now()->addDays(7)->endOfDay())
                                    ->after('start_date')
                                    ->displayFormat('d M Y H:i')
                                    ->native(false)
                                    ->required(),
                            ])
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        // For virtual model, filtering is handled in the page
                        return $query;
                    })
                    ->indicateUsing(function (array $data): ?string {
                        $start = $data['start_date'] ?? now()->format('Y-m-d H:i');
                        $end = $data['end_date'] ?? now()->addDays(7)->format('Y-m-d H:i');

                        return 'Period: ' . Carbon::parse($start)->format('M d H:i') . ' - ' . Carbon::parse($end)->format('M d H:i');
                    }),
            ])
            ->actions([
                Action::make('view_transactions')
                    ->label('View All Transactions')
                    ->icon('heroicon-m-document-text')
                    ->url(function ($record) {
                        try {
                            // Link to transactions index with filter for this item
                            return route('filament.admin.resources.transactions.index');
                        } catch (\Exception $e) {
                            return null;
                        }
                    })
                    ->openUrlInNewTab()
                    ->color('primary'),

                Action::make('edit_item')
                    ->label('Edit Item')
                    ->icon('heroicon-m-pencil-square')
                    ->url(function ($record) {
                        try {
                            if ($record->type === 'product') {
                                $productId = str_replace('product_', '', $record->id);
                                return route('filament.admin.resources.products.edit', [
                                    'record' => $productId
                                ]);
                            } elseif ($record->type === 'bundling') {
                                $bundlingId = str_replace('bundling_', '', $record->id);
                                return route('filament.admin.resources.bundlings.edit', [
                                    'record' => $bundlingId
                                ]);
                            }
                        } catch (\Exception $e) {
                            return null;
                        }
                        return null;
                    })
                    ->openUrlInNewTab()
                    ->color('gray')
                    ->visible(fn($record) => $record->type === 'product' || $record->type === 'bundling'),
            ])
            ->headerActions([
                Action::make('help')
                    ->label('❓ Help')
                    ->icon('heroicon-o-question-mark-circle')
                    ->color('info')
                    ->modalHeading('📖 Product Availability Help')
                    ->modalSubheading('How to use the Product Availability Search')
                    ->modalContent(new HtmlString('
                        <div class="space-y-4">
                            <div>
                                <h3 class="font-semibold text-lg">📝 How to Search:</h3>
                                <ol class="list-decimal ml-6 space-y-2 mt-2">
                                    <li>Fill the form above to select products/bundlings</li>
                                    <li>Set the date range for availability check</li>
                                    <li>Click <strong>"🔍 Search Availability"</strong> button</li>
                                    <li>View results in the table below</li>
                                </ol>
                            </div>
                            <div>
                                <h3 class="font-semibold text-lg">📊 Understanding Results:</h3>
                                <ul class="list-disc ml-6 space-y-2 mt-2">
                                    <li><span class="text-green-600">Green</span>: High availability (70%+)</li>
                                    <li><span class="text-yellow-600">Yellow</span>: Medium availability (30-70%)</li>
                                    <li><span class="text-red-600">Red</span>: Low availability (<30%)</li>
                                    <li><strong>Current Rentals</strong>: Shows active bookings with customer links</li>
                                </ul>
                            </div>
                            <div>
                                <h3 class="font-semibold text-lg">🔗 Quick Actions:</h3>
                                <ul class="list-disc ml-6 space-y-2 mt-2">
                                    <li>Click customer names to edit transactions</li>
                                    <li>Use "View All Transactions" to see transaction list</li>
                                    <li>Use "Edit Item" to modify product/bundling details</li>
                                </ul>
                            </div>
                        </div>
                    '))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Got it!'),
            ])
            ->emptyStateHeading('🔍 Search Products & Bundlings Availability')
            ->emptyStateDescription('Click "🔍 Search Availability" to select items and check their availability for specific dates.')
            ->emptyStateIcon('heroicon-o-magnifying-glass')
            ->defaultSort('name', 'asc')
            ->searchable(false)
            ->recordUrl(null);
    }

    /**
     * Override the default records method to use our virtual model data
     */
    public static function getEloquentQuery(): Builder
    {
        // This creates a fake query that we'll replace with our virtual data
        return ProductAvailability::query()->whereRaw('1 = 0');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductAvailabilities::route('/'),
        ];
    }
}
