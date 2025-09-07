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
            ->modifyQueryUsing(function (Builder $query) {
                // By default, show no results until form is submitted
                $selectedProducts = request('selected_products', []);
                $selectedBundlings = request('selected_bundlings', []);
                
                // Only show results if selections are made
                if (empty($selectedProducts) && empty($selectedBundlings)) {
                    return $query->whereRaw('1 = 0'); // Return empty result set
                }
                
                // Filter by selected products only
                if (!empty($selectedProducts)) {
                    return $query->whereIn('id', $selectedProducts)->with(['items', 'bundlings']);
                }
                
                return $query->whereRaw('1 = 0'); // Return empty if only bundlings selected
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
                        // Check if this is a Product or Bundling
                        if ($record instanceof \App\Models\Product) {
                            return $record->items()->count();
                        }
                        // For bundlings, show total items across all products
                        if ($record instanceof \App\Models\Bundling) {
                            return $record->products()->withSum('items as total_items', 'id')->get()->sum('total_items');
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

                        return static::getAvailableItemsCount($record->id, $startDate, $endDate);
                    })
                    ->alignCenter()
                    ->color(function ($state, $record) {
                        // Handle both Product and Bundling models
                        $total = 0;
                        if ($record instanceof \App\Models\Product) {
                            $total = $record->items()->count();
                        } elseif ($record instanceof \App\Models\Bundling) {
                            $total = $record->products()->withSum('items as total_items', 'id')->get()->sum('total_items');
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

                        if ($record instanceof \App\Models\Product) {
                            $available = static::getAvailableItemsCount($record->id, $startDate, $endDate);
                            $total = $record->items()->count();
                        } else {
                            // For bundlings, calculate differently
                            $total = $record->products()->withSum('items as total_items', 'id')->get()->sum('total_items');
                            $available = $total; // Simplified for now
                        }

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
                        $startDate = request('start_date');
                        $endDate = request('end_date');

                        if (!$startDate || !$endDate) {
                            $startDate = now()->format('Y-m-d H:i:s');
                            $endDate = now()->addDays(7)->endOfDay()->format('Y-m-d H:i:s');
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
