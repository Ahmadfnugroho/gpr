<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductAvailabilityResource\Pages;
use App\Models\Product;
use App\Models\ProductItem;
use App\Models\DetailTransactionProductItem;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Support\Enums\FontWeight;
use Illuminate\Support\HtmlString;

class ProductAvailabilityResource extends Resource
{
    protected static ?string $model = Product::class;

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
                // This resource is read-only, no form needed
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultPaginationPageOption(50)
            ->modifyQueryUsing(function (Builder $query) {
                return $query->with(['productItems']);
            })
            ->columns([
                TextColumn::make('name')
                    ->label('Product Name')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::SemiBold)
                    ->wrap(),
                
                TextColumn::make('total_items')
                    ->label('Total Items')
                    ->getStateUsing(function ($record) {
                        return $record->productItems()->count();
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
                        $total = $record->productItems()->count();
                        if ($total == 0) return 'gray';
                        $percentage = ($state / $total) * 100;
                        if ($percentage >= 70) return 'success';
                        if ($percentage >= 30) return 'warning';
                        return 'danger';
                    })
                    ->weight(FontWeight::Bold),

                TextColumn::make('rental_status')
                    ->label('Current Rentals')
                    ->getStateUsing(function ($record) {
                        $startDate = request('tableFilters.date_range.start_date');
                        $endDate = request('tableFilters.date_range.end_date');
                        
                        if (!$startDate || !$endDate) {
                            $startDate = now()->format('Y-m-d');
                            $endDate = now()->addDays(7)->format('Y-m-d');
                        }
                        
                        return static::getRentalStatusInfo($record->id, $startDate, $endDate);
                    })
                    ->html()
                    ->wrap(),

                TextColumn::make('next_available_date')
                    ->label('Next Available')
                    ->getStateUsing(function ($record) {
                        return static::getNextAvailableDate($record->id);
                    })
                    ->date('d M Y')
                    ->placeholder('Available now')
                    ->sortable(false),

                TextColumn::make('serial_numbers')
                    ->label('Available Serial Numbers')
                    ->getStateUsing(function ($record) {
                        $startDate = request('tableFilters.date_range.start_date');
                        $endDate = request('tableFilters.date_range.end_date');
                        
                        if (!$startDate || !$endDate) {
                            $startDate = now()->format('Y-m-d');
                            $endDate = now()->addDays(7)->format('Y-m-d');
                        }
                        
                        return static::getAvailableSerialNumbers($record->id, $startDate, $endDate);
                    })
                    ->wrap()
                    ->limit(50)
                    ->placeholder('No items available'),
            ])
            ->filters([
                Filter::make('date_range')
                    ->form([
                        Grid::make(2)
                            ->schema([
                                DatePicker::make('start_date')
                                    ->label('Start Date')
                                    ->default(now())
                                    ->native(false)
                                    ->displayFormat('d M Y')
                                    ->required(),
                                DatePicker::make('end_date')
                                    ->label('End Date')  
                                    ->default(now()->addDays(7))
                                    ->native(false)
                                    ->displayFormat('d M Y')
                                    ->required()
                                    ->after('start_date'),
                            ])
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        // The actual filtering is handled in the column getStateUsing methods
                        // This filter just provides the date inputs
                        return $query;
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (!$data['start_date'] || !$data['end_date']) {
                            return null;
                        }

                        return 'Date range: ' . Carbon::parse($data['start_date'])->format('d M Y') 
                             . ' - ' . Carbon::parse($data['end_date'])->format('d M Y');
                    }),

                Filter::make('availability_status')
                    ->label('Availability Status')
                    ->toggle()
                    ->query(function (Builder $query, array $data): Builder {
                        if (!$data['value']) {
                            return $query;
                        }

                        $startDate = request('tableFilters.date_range.start_date', now()->format('Y-m-d'));
                        $endDate = request('tableFilters.date_range.end_date', now()->addDays(7)->format('Y-m-d'));

                        return $query->whereHas('productItems', function ($q) use ($startDate, $endDate) {
                            $q->whereNotExists(function ($subQuery) use ($startDate, $endDate) {
                                $subQuery->select('*')
                                    ->from('detail_transaction_product_items as dtpi')
                                    ->join('detail_transactions as dt', 'dtpi.detail_transaction_id', '=', 'dt.id')
                                    ->join('transactions as t', 'dt.transaction_id', '=', 't.id')
                                    ->whereColumn('dtpi.product_item_id', 'product_items.id')
                                    ->where('t.booking_status', '!=', 'cancel')
                                    ->where(function ($q) use ($startDate, $endDate) {
                                        $q->whereBetween('t.start_date', [$startDate, $endDate])
                                          ->orWhereBetween('t.end_date', [$startDate, $endDate])
                                          ->orWhere(function ($q2) use ($startDate, $endDate) {
                                              $q2->where('t.start_date', '<=', $startDate)
                                                 ->where('t.end_date', '>=', $endDate);
                                          });
                                    });
                            });
                        });
                    })
                    ->indicateUsing(function (array $data): ?string {
                        return $data['value'] ? 'Only available products' : null;
                    }),
            ])
            ->actions([
                // No actions needed for read-only resource
            ])
            ->bulkActions([
                // No bulk actions needed for read-only resource
            ])
            ->defaultSort('name')
            ->poll('30s') // Auto-refresh every 30 seconds for real-time data
            ->emptyStateHeading('No products found')
            ->emptyStateDescription('There are no products in the system or none match your current filters.')
            ->emptyStateIcon('heroicon-o-calendar-days');
    }

    /**
     * Get count of available items for a product in date range
     */
    protected static function getAvailableItemsCount(int $productId, string $startDate, string $endDate): int
    {
        $usedItemIds = DetailTransactionProductItem::whereHas('detailTransaction.transaction', function ($query) use ($startDate, $endDate) {
            $query->where('booking_status', '!=', 'cancel')
                  ->where(function ($q) use ($startDate, $endDate) {
                      $q->whereBetween('start_date', [$startDate, $endDate])
                        ->orWhereBetween('end_date', [$startDate, $endDate])
                        ->orWhere(function ($q2) use ($startDate, $endDate) {
                            $q2->where('start_date', '<=', $startDate)
                               ->where('end_date', '>=', $endDate);
                        });
                  });
        })
        ->whereHas('productItem', function ($q) use ($productId) {
            $q->where('product_id', $productId);
        })
        ->pluck('product_item_id')
        ->unique();

        return ProductItem::where('product_id', $productId)
            ->whereNotIn('id', $usedItemIds)
            ->count();
    }

    /**
     * Get rental status information for a product
     */
    protected static function getRentalStatusInfo(int $productId, string $startDate, string $endDate): string
    {
        $rentals = DetailTransactionProductItem::whereHas('detailTransaction.transaction', function ($query) use ($startDate, $endDate) {
            $query->whereIn('booking_status', ['booking', 'paid', 'on_rented'])
                  ->where(function ($q) use ($startDate, $endDate) {
                      $q->whereBetween('start_date', [$startDate, $endDate])
                        ->orWhereBetween('end_date', [$startDate, $endDate])
                        ->orWhere(function ($q2) use ($startDate, $endDate) {
                            $q2->where('start_date', '<=', $startDate)
                               ->where('end_date', '>=', $endDate);
                        });
                  });
        })
        ->whereHas('productItem', function ($q) use ($productId) {
            $q->where('product_id', $productId);
        })
        ->with(['detailTransaction.transaction'])
        ->get()
        ->groupBy('detailTransaction.transaction.booking_status');

        $statusInfo = [];
        foreach (['booking', 'paid', 'on_rented'] as $status) {
            $count = $rentals->get($status, collect())->count();
            if ($count > 0) {
                $color = match($status) {
                    'booking' => 'orange',
                    'paid' => 'blue', 
                    'on_rented' => 'green',
                    default => 'gray'
                };
                $statusInfo[] = "<span style='color: {$color}; font-weight: bold;'>{$count} {$status}</span>";
            }
        }

        return empty($statusInfo) ? 'No active rentals' : implode(' • ', $statusInfo);
    }

    /**
     * Get next available date for a product
     */
    protected static function getNextAvailableDate(int $productId): ?string
    {
        $nextRental = DetailTransactionProductItem::whereHas('detailTransaction.transaction', function ($query) {
            $query->where('booking_status', '!=', 'cancel')
                  ->where('end_date', '>', now());
        })
        ->whereHas('productItem', function ($q) use ($productId) {
            $q->where('product_id', $productId);
        })
        ->with(['detailTransaction.transaction'])
        ->get()
        ->min('detailTransaction.transaction.end_date');

        return $nextRental ? Carbon::parse($nextRental)->addDay()->format('Y-m-d') : null;
    }

    /**
     * Get available serial numbers for a product in date range
     */
    protected static function getAvailableSerialNumbers(int $productId, string $startDate, string $endDate): string
    {
        $usedItemIds = DetailTransactionProductItem::whereHas('detailTransaction.transaction', function ($query) use ($startDate, $endDate) {
            $query->where('booking_status', '!=', 'cancel')
                  ->where(function ($q) use ($startDate, $endDate) {
                      $q->whereBetween('start_date', [$startDate, $endDate])
                        ->orWhereBetween('end_date', [$startDate, $endDate])
                        ->orWhere(function ($q2) use ($startDate, $endDate) {
                            $q2->where('start_date', '<=', $startDate)
                               ->where('end_date', '>=', $endDate);
                        });
                  });
        })
        ->whereHas('productItem', function ($q) use ($productId) {
            $q->where('product_id', $productId);
        })
        ->pluck('product_item_id')
        ->unique();

        $availableSerials = ProductItem::where('product_id', $productId)
            ->whereNotIn('id', $usedItemIds)
            ->pluck('serial_number')
            ->sort()
            ->values();

        if ($availableSerials->isEmpty()) {
            return '';
        }

        if ($availableSerials->count() <= 10) {
            return $availableSerials->implode(', ');
        }

        $first5 = $availableSerials->take(5)->implode(', ');
        $remaining = $availableSerials->count() - 5;
        
        return $first5 . " <span style='color: #6b7280; font-style: italic;'>and {$remaining} more</span>";
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductAvailabilities::route('/'),
        ];
    }
}
