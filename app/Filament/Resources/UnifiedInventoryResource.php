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
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Support\Enums\FontWeight;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Collection;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Forms\Components\Actions;
use Illuminate\Support\Facades\DB;

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
            ->columns([
                TextColumn::make('item_type')
                    ->label('Type')
                    ->getStateUsing(function ($record) {
                        // Check if this is from unified type or regular property
                        return $record->item_type ?? ($record->unified_type === 'bundling' ? 'Bundle' : 'Product');
                    })
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Product' => 'success',
                        'Bundle' => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::SemiBold)
                    ->wrap()
                    ->description(function ($record) {
                        if (isset($record->unified_type) && $record->unified_type === 'bundling') {
                            // For bundlings, show contained products
                            if (isset($record->original_bundling)) {
                                $productNames = $record->original_bundling->products->pluck('name')->take(3)->implode(', ');
                                $remaining = $record->original_bundling->products->count() - 3;
                                $suffix = $remaining > 0 ? " (+{$remaining} more)" : '';
                                return "Contains products: {$productNames}{$suffix}";
                            }
                        } else {
                            // For products, show bundlings it's used in
                            $bundlings = $record->bundlings ?? collect();
                            if ($bundlings->isNotEmpty()) {
                                $bundlingNames = $bundlings->pluck('name')->take(3)->implode(', ');
                                $remaining = $bundlings->count() - 3;
                                $suffix = $remaining > 0 ? " (+{$remaining} more)" : '';
                                return "Used in bundles: {$bundlingNames}{$suffix}";
                            }
                        }
                        return null;
                    }),

                TextColumn::make('total_items')
                    ->label('Total Items')
                    ->getStateUsing(function ($record) {
                        if (isset($record->unified_type) && $record->unified_type === 'bundling') {
                            // For bundlings from unified view
                            return $record->total_bundle_items ?? 0;
                        }
                        // For products or regular Product instances
                        if ($record instanceof \App\Models\Product || method_exists($record, 'items')) {
                            return $record->items()->count();
                        }
                        return 0;
                    })
                    ->alignCenter()
                    ->sortable(false),

                TextColumn::make('available_items')
                    ->label('Available Items')
                    ->getStateUsing(function ($record) {
                        $startDate = request('start_date');
                        $endDate = request('end_date');

                        if (!$startDate || !$endDate) {
                            $startDate = now()->format('Y-m-d H:i:s');
                            $endDate = now()->addDays(7)->endOfDay()->format('Y-m-d H:i:s');
                        }

                        if (isset($record->unified_type) && $record->unified_type === 'bundling') {
                            // For bundlings, calculate minimum available items across all products
                            if (isset($record->original_bundling)) {
                                $minAvailable = PHP_INT_MAX;
                                foreach ($record->original_bundling->products as $product) {
                                    $available = static::getAvailableItemsCount($product->id, $startDate, $endDate);
                                    $minAvailable = min($minAvailable, $available);
                                }
                                return $minAvailable === PHP_INT_MAX ? 0 : $minAvailable;
                            }
                            return 0;
                        }

                        return static::getAvailableItemsCount($record->id, $startDate, $endDate);
                    })
                    ->alignCenter()
                    ->color(function ($state, $record) {
                        // Get total items for percentage calculation
                        $total = 0;
                        if (isset($record->unified_type) && $record->unified_type === 'bundling') {
                            $total = $record->total_bundle_items ?? 0;
                        } else {
                            $total = $record->items()->count();
                        }
                        
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
                        $startDate = request('start_date');
                        $endDate = request('end_date');

                        if (!$startDate || !$endDate) {
                            $startDate = now()->format('Y-m-d H:i:s');
                            $endDate = now()->addDays(7)->endOfDay()->format('Y-m-d H:i:s');
                        }

                        if (isset($record->unified_type) && $record->unified_type === 'bundling') {
                            // For bundlings, calculate minimum available percentage
                            if (isset($record->original_bundling)) {
                                $minPercentage = 100;
                                foreach ($record->original_bundling->products as $product) {
                                    $available = static::getAvailableItemsCount($product->id, $startDate, $endDate);
                                    $total = $product->items()->count();
                                    if ($total > 0) {
                                        $percentage = round(($available / $total) * 100);
                                        $minPercentage = min($minPercentage, $percentage);
                                    } else {
                                        $minPercentage = 0;
                                    }
                                }
                                return "{$minPercentage}%";
                            }
                            return '0%';
                        } else {
                            // For products
                            $available = static::getAvailableItemsCount($record->id, $startDate, $endDate);
                            $total = $record->items()->count();
                            if ($total == 0) return '0%';
                            $percentage = round(($available / $total) * 100);
                            return "{$percentage}%";
                        }
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
                        $startDate = request('start_date');
                        $endDate = request('end_date');

                        if (!$startDate || !$endDate) {
                            $startDate = now()->format('Y-m-d H:i:s');
                            $endDate = now()->addDays(7)->endOfDay()->format('Y-m-d H:i:s');
                        }

                        if (isset($record->unified_type) && $record->unified_type === 'bundling') {
                            // For bundlings, get rentals from all contained products
                            if (isset($record->original_bundling)) {
                                $allRentals = collect();
                                foreach ($record->original_bundling->products as $product) {
                                    $activeRentals = DetailTransactionProductItem::with('detailTransaction.transaction')
                                        ->whereHas('detailTransaction.transaction', function ($query) use ($startDate, $endDate) {
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
                                        ->whereHas('productItem', function ($query) use ($product) {
                                            $query->where('product_id', $product->id);
                                        })
                                        ->get();
                                    $allRentals = $allRentals->merge($activeRentals);
                                }
                                
                                if ($allRentals->isEmpty()) {
                                    return 'No active bundle rentals';
                                }
                                
                                // Group by transaction for bundling display
                                $transactions = $allRentals->groupBy('detailTransaction.transaction.id');
                                $rentalInfo = [];
                                foreach ($transactions->take(3) as $transactionRentals) {
                                    $transaction = $transactionRentals->first()->detailTransaction->transaction;
                                    $startDate = $transaction->start_date ? $transaction->start_date->format('d/m/Y') : 'N/A';
                                    $endDate = $transaction->end_date ? $transaction->end_date->format('d/m/Y') : 'N/A';
                                    $status = ucfirst($transaction->booking_status);
                                    $rentalInfo[] = "{$startDate} - {$endDate} ({$status})";
                                }
                                
                                $remaining = $transactions->count() - 3;
                                if ($remaining > 0) {
                                    $rentalInfo[] = "+{$remaining} more transactions";
                                }
                                
                                return implode('\n', $rentalInfo);
                            }
                            return 'No bundle data';
                        }

                        // For products
                        $activeRentals = DetailTransactionProductItem::with('detailTransaction.transaction')
                            ->whereHas('detailTransaction.transaction', function ($query) use ($startDate, $endDate) {
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
                            ->whereHas('productItem', function ($query) use ($record) {
                                $query->where('product_id', $record->id);
                            })
                            ->get();

                        if ($activeRentals->isEmpty()) {
                            return 'No active rentals';
                        }

                        $rentalInfo = [];
                        foreach ($activeRentals->take(3) as $rental) {
                            $transaction = $rental->detailTransaction->transaction;
                            $startDate = $transaction->start_date ? $transaction->start_date->format('d/m/Y') : 'N/A';
                            $endDate = $transaction->end_date ? $transaction->end_date->format('d/m/Y') : 'N/A';
                            $status = ucfirst($transaction->booking_status);
                            $rentalInfo[] = "{$startDate} - {$endDate} ({$status})";
                        }

                        $remaining = $activeRentals->count() - 3;
                        if ($remaining > 0) {
                            $rentalInfo[] = "+{$remaining} more";
                        }

                        return implode('\n', $rentalInfo);
                    })
                    ->wrap()
                    ->lineClamp(4)
                    ->color('warning')
                    ->icon('heroicon-o-clock')
                    ->tooltip(function ($record) {
                        $startDate = request('start_date');
                        $endDate = request('end_date');

                        if (!$startDate || !$endDate) {
                            $startDate = now()->format('Y-m-d H:i:s');
                            $endDate = now()->addDays(7)->endOfDay()->format('Y-m-d H:i:s');
                        }

                        if (isset($record->unified_type) && $record->unified_type === 'bundling') {
                            return 'Bundle rentals include all products within the bundle';
                        }

                        $activeRentals = DetailTransactionProductItem::with('detailTransaction.transaction')
                            ->whereHas('detailTransaction.transaction', function ($query) use ($startDate, $endDate) {
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
                            ->whereHas('productItem', function ($query) use ($record) {
                                $query->where('product_id', $record->id);
                            })
                            ->get();

                        if ($activeRentals->isEmpty()) {
                            return 'No active rentals in selected period';
                        }

                        $tooltipInfo = [];
                        foreach ($activeRentals as $rental) {
                            $transaction = $rental->detailTransaction->transaction;
                            $startDate = $transaction->start_date ? $transaction->start_date->format('d M Y H:i') : 'N/A';
                            $endDate = $transaction->end_date ? $transaction->end_date->format('d M Y H:i') : 'N/A';
                            $status = ucfirst($transaction->booking_status);
                            $tooltipInfo[] = "• {$startDate} - {$endDate} ({$status})";
                        }

                        return implode('\n', $tooltipInfo);
                    }),
            ])
            ->filters([
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
                    ->url(fn ($record) => route('filament.admin.resources.products.edit', ['record' => $record->id]))
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
            ->emptyStateHeading('🔍 Pilih Produk/Bundling untuk Melihat Ketersediaan')
            ->emptyStateDescription('📝 Langkah-langkah:\n1. Klik tombol "🔍 Cari Ketersediaan" di header\n2. Pilih produk atau bundling yang ingin dicek\n3. Atur tanggal periode pengecekan\n4. Klik "🔍 Cari Sekarang" untuk melihat hasil')
            ->emptyStateIcon('heroicon-o-magnifying-glass')
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
