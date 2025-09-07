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
                // Form moved to custom view above table
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
