<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UnifiedInventoryResource\Pages;
use App\Models\Product;
use App\Models\Bundling;
use App\Models\ProductItem;
use App\Models\DetailTransactionProductItem;
use Carbon\Carbon;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Support\Enums\FontWeight;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Collection;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\Action;

class UnifiedInventoryResource extends Resource
{
    protected static ?string $model = Product::class; // Using Product as base model

    protected static ?string $navigationIcon = 'heroicon-o-cube-transparent';
    protected static ?string $navigationGroup = 'Inventory Management';
    protected static ?string $navigationLabel = 'Product & Bundling Availability';
    protected static ?int $navigationSort = 20;
    protected static ?string $slug = 'unified-inventory';

    /**
     * Get available items count for a product in a date range
     */
    protected static function getAvailableItemsCount($productId, $startDate, $endDate): int
    {
        try {
            $start = Carbon::parse($startDate);
            $end = Carbon::parse($endDate);
        } catch (\Exception $e) {
            return 0;
        }

        $totalItems = ProductItem::where('product_id', $productId)->count();

        $usedItems = DetailTransactionProductItem::whereHas('detailTransaction.transaction', function ($query) use ($start, $end) {
            $query->whereIn('booking_status', ['booking', 'paid', 'on_rented'])
                ->where(function ($q) use ($start, $end) {
                    $q->whereBetween('start_date', [$start, $end])
                        ->orWhereBetween('end_date', [$start, $end])
                        ->orWhere(function ($q2) use ($start, $end) {
                            $q2->where('start_date', '<=', $start)
                                ->where('end_date', '>=', $end);
                        });
                });
        })->whereHas('productItem', function ($query) use ($productId) {
            $query->where('product_id', $productId);
        })->count();

        return max(0, $totalItems - $usedItems);
    }

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
                // This resource is read-only, no form needed
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultPaginationPageOption(50)
            ->modifyQueryUsing(function (Builder $query) {
                $searchTerm = request('tableSearch');
                
                if ($searchTerm && strlen(trim($searchTerm)) >= 2) {
                    $keywords = array_filter(array_map('trim', explode(' ', strtolower($searchTerm))));
                    
                    if (!empty($keywords)) {
                        $query->where(function ($q) use ($keywords) {
                            foreach ($keywords as $keyword) {
                                $q->whereRaw('LOWER(name) LIKE ?', ["%{$keyword}%"]);
                            }
                        });
                    }
                }
                
                return $query->with(['items', 'bundlings']);
            })
            ->columns([
                TextColumn::make('item_type')
                    ->label('Type')
                    ->getStateUsing(function ($record) {
                        return 'Product';
                    })
                    ->badge()
                    ->color('primary'),

                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::SemiBold)
                    ->wrap()
                    ->description(function ($record) {
                        $bundlings = $record->bundlings ?? collect();
                        if ($bundlings->isNotEmpty()) {
                            $bundlingNames = $bundlings->pluck('name')->take(3)->implode(', ');
                            $remaining = $bundlings->count() - 3;
                            $suffix = $remaining > 0 ? " (+{$remaining} more)" : '';
                            return "Used in bundles: {$bundlingNames}{$suffix}";
                        }
                        return null;
                    }),

                TextColumn::make('total_items')
                    ->label('Total Items')
                    ->getStateUsing(function ($record) {
                        return $record->items()->count();
                    })
                    ->alignCenter()
                    ->sortable(false),

                TextColumn::make('available_items')
                    ->label('Available Items')
                    ->getStateUsing(function ($record) {
                        $startDate = request('tableFilters.date_range.start_date');
                        $endDate = request('tableFilters.date_range.end_date');

                        if (!$startDate || !$endDate) {
                            $startDate = now()->format('Y-m-d');
                            $endDate = now()->addDays(7)->format('Y-m-d');
                        }

                        return static::getAvailableItemsCount($record->id, $startDate, $endDate);
                    })
                    ->alignCenter()
                    ->color(function ($state, $record) {
                        $total = $record->items()->count();
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

                        $available = static::getAvailableItemsCount($record->id, $startDate, $endDate);
                        $total = $record->items()->count();

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

                TextColumn::make('current_rentals')
                    ->label('Current Rentals')
                    ->getStateUsing(function ($record) {
                        $startDate = request('tableFilters.date_range.start_date');
                        $endDate = request('tableFilters.date_range.end_date');

                        if (!$startDate || !$endDate) {
                            $startDate = now()->format('Y-m-d');
                            $endDate = now()->addDays(7)->format('Y-m-d');
                        }

                        $activeRentals = DetailTransactionProductItem::whereHas('detailTransaction.transaction', function ($query) use ($startDate, $endDate) {
                            $query->whereIn('booking_status', ['booking', 'paid', 'on_rented'])
                                ->where(function ($q) use ($startDate, $endDate) {
                                    $q->whereBetween('start_date', [$startDate, $endDate])
                                        ->orWhereBetween('end_date', [$startDate, $endDate])
                                        ->orWhere(function ($q2) use ($startDate, $endDate) {
                                            $q2->where('start_date', '<=', $startDate)
                                                ->where('end_date', '>=', $endDate);
                                        });
                                });
                        })->whereHas('productItem', function ($query) use ($record) {
                            $query->where('product_id', $record->id);
                        })->count();

                        return $activeRentals;
                    })
                    ->alignCenter()
                    ->color('warning')
                    ->icon('heroicon-o-clock'),
            ])
            ->filters([
                Filter::make('date_range')
                    ->form([
                        Grid::make(2)
                            ->schema([
                                DatePicker::make('start_date')
                                    ->label('Start Date')
                                    ->default(now())
                                    ->maxDate(now()->addYear())
                                    ->native(false)
                                    ->displayFormat('d M Y'),
                                DatePicker::make('end_date')
                                    ->label('End Date')
                                    ->default(now()->addDays(7))
                                    ->after('start_date')
                                    ->maxDate(now()->addYear())
                                    ->native(false)
                                    ->displayFormat('d M Y'),
                            ])
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        // The query filtering is handled in the column state calculation
                        return $query;
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['start_date'] ?? null) {
                            $indicators['start_date'] = 'Start: ' . Carbon::parse($data['start_date'])->format('d M Y');
                        }
                        if ($data['end_date'] ?? null) {
                            $indicators['end_date'] = 'End: ' . Carbon::parse($data['end_date'])->format('d M Y');
                        }
                        return $indicators;
                    }),

                SelectFilter::make('availability_status')
                    ->label('Availability Status')
                    ->options([
                        'all' => 'All Items',
                        'available' => 'Available (>0 items)',
                        'low' => 'Low Stock (<30%)',
                        'unavailable' => 'Unavailable (0 items)'
                    ])
                    ->default('all')
                    ->query(function (Builder $query, array $data): Builder {
                        // For now, return the query as is
                        return $query;
                    }),
            ])
            ->actions([
                Action::make('view_details')
                    ->label('View Details')
                    ->icon('heroicon-o-eye')
                    ->color('primary')
                    ->url(fn ($record) => route('filament.admin.resources.products.view', ['record' => $record->id]))
                    ->openUrlInNewTab(),
            ])
            ->headerActions([
                Action::make('help')
                    ->label('Help')
                    ->icon('heroicon-o-question-mark-circle')
                    ->color('info')
                    ->modalHeading('Product & Bundling Availability Help')
                    ->modalContent(view('filament.pages.product-availability-help'))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
            ])
            ->emptyStateHeading('No inventory items found')
            ->emptyStateDescription('Try adjusting your search terms or filters.')
            ->emptyStateIcon('heroicon-o-cube-transparent')
            ->striped()
            ->poll('30s'); // Auto-refresh every 30 seconds
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUnifiedInventories::route('/'),
        ];
    }
}
