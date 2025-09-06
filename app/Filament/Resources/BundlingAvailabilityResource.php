<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BundlingAvailabilityResource\Pages;
use App\Models\Bundling;
use App\Models\DetailTransaction;
use Carbon\Carbon;
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

class BundlingAvailabilityResource extends Resource
{
    protected static ?string $model = Bundling::class;

    // This resource is now deprecated - use InventoryAvailabilityResource instead
    protected static ?string $navigationIcon = 'heroicon-o-cube';
    protected static ?string $navigationGroup = null; // Hidden from navigation
    protected static ?string $navigationLabel = 'Bundling Availability (Deprecated)';
    protected static ?int $navigationSort = 26;
    
    // Hide from navigation
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
    protected static ?string $slug = 'bundling-availability';

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
                    // Split search term into individual keywords
                    $keywords = array_filter(array_map('trim', explode(' ', strtolower($searchTerm))));
                    
                    if (!empty($keywords)) {
                        $query->where(function ($q) use ($keywords) {
                            // For each keyword, it must be present in the bundling name (AND logic)
                            foreach ($keywords as $keyword) {
                                $q->whereRaw('LOWER(name) LIKE ?', ["%{$keyword}%"]);
                            }
                        });
                    }
                }
                
                return $query->with(['products.items']);
            })
            ->columns([
                TextColumn::make('name')
                    ->label('Bundling Name')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::SemiBold)
                    ->wrap()
                    ->suffix(' (Bundle)')
                    ->description(function ($record) {
                        $productNames = $record->products->pluck('name')->take(3)->implode(', ');
                        $remaining = $record->products->count() - 3;
                        $suffix = $remaining > 0 ? " (+{$remaining} more)" : '';
                        return "Contains: {$productNames}{$suffix}";
                    }),

                TextColumn::make('total_quantity')
                    ->label('Total Bundles')
                    ->getStateUsing(function ($record) {
                        $startDate = request('tableFilters.date_range.start_date');
                        $endDate = request('tableFilters.date_range.end_date');

                        if (!$startDate || !$endDate) {
                            $startDate = now()->format('Y-m-d');
                            $endDate = now()->addDays(7)->format('Y-m-d');
                        }

                        // Calculate the maximum possible bundles based on product availability
                        $minAvailable = null;
                        foreach ($record->products as $product) {
                            $requiredPerBundle = $product->pivot->quantity ?? 1;
                            $totalItems = $product->items()->count();
                            $maxBundlesFromThisProduct = intdiv($totalItems, $requiredPerBundle);
                            
                            $minAvailable = is_null($minAvailable) 
                                ? $maxBundlesFromThisProduct 
                                : min($minAvailable, $maxBundlesFromThisProduct);
                        }
                        
                        return $minAvailable ?? 0;
                    })
                    ->alignCenter()
                    ->sortable(false),

                TextColumn::make('available_quantity')
                    ->label('Available Bundles')
                    ->getStateUsing(function ($record) {
                        $startDate = request('tableFilters.date_range.start_date');
                        $endDate = request('tableFilters.date_range.end_date');

                        if (!$startDate || !$endDate) {
                            $startDate = now()->format('Y-m-d');
                            $endDate = now()->addDays(7)->format('Y-m-d');
                        }

                        return $record->getAvailableQuantityForPeriod(
                            Carbon::parse($startDate),
                            Carbon::parse($endDate)
                        );
                    })
                    ->alignCenter()
                    ->color(function ($state, $record) {
                        $startDate = request('tableFilters.date_range.start_date', now()->format('Y-m-d'));
                        $endDate = request('tableFilters.date_range.end_date', now()->addDays(7)->format('Y-m-d'));
                        
                        // Get total possible bundles
                        $minAvailable = null;
                        foreach ($record->products as $product) {
                            $requiredPerBundle = $product->pivot->quantity ?? 1;
                            $totalItems = $product->items()->count();
                            $maxBundlesFromThisProduct = intdiv($totalItems, $requiredPerBundle);
                            
                            $minAvailable = is_null($minAvailable) 
                                ? $maxBundlesFromThisProduct 
                                : min($minAvailable, $maxBundlesFromThisProduct);
                        }
                        
                        $total = $minAvailable ?? 1;
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

                        $activeRentals = $record->detailTransactions()
                            ->whereHas('transaction', function ($query) use ($startDate, $endDate) {
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
                            ->with(['transaction'])
                            ->get()
                            ->groupBy('transaction.booking_status');

                        $statusInfo = [];
                        foreach (['booking', 'paid', 'on_rented'] as $status) {
                            $count = $activeRentals->get($status, collect())->count();
                            if ($count > 0) {
                                $color = match ($status) {
                                    'booking' => 'orange',
                                    'paid' => 'blue',
                                    'on_rented' => 'green',
                                    default => 'gray'
                                };
                                $statusInfo[] = "<span style='color: {$color}; font-weight: bold;'>{$count} {$status}</span>";
                            }
                        }

                        return empty($statusInfo) ? 'No active rentals' : implode(' • ', $statusInfo);
                    })
                    ->html()
                    ->wrap(),

                TextColumn::make('next_available_date')
                    ->label('Next Available')
                    ->getStateUsing(function ($record) {
                        $nextRental = $record->detailTransactions()
                            ->whereHas('transaction', function ($query) {
                                $query->where('booking_status', '!=', 'cancel')
                                    ->where('end_date', '>', now());
                            })
                            ->with(['transaction'])
                            ->get()
                            ->min('transaction.end_date');

                        return $nextRental ? Carbon::parse($nextRental)->addDay()->format('Y-m-d') : null;
                    })
                    ->date('d M Y')
                    ->placeholder('Available now')
                    ->sortable(false),

                TextColumn::make('products_detail')
                    ->label('Product Details')
                    ->getStateUsing(function ($record) {
                        $details = [];
                        foreach ($record->products as $product) {
                            $quantity = $product->pivot->quantity ?? 1;
                            $details[] = "{$product->name} ({$quantity}x)";
                        }
                        return implode(', ', $details);
                    })
                    ->wrap()
                    ->limit(100)
                    ->placeholder('No products'),
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
                    ->label('Available Only')
                    ->toggle()
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['value']) || !$data['value']) {
                            return $query;
                        }

                        $startDate = request('tableFilters.date_range.start_date', now()->format('Y-m-d'));
                        $endDate = request('tableFilters.date_range.end_date', now()->addDays(7)->format('Y-m-d'));

                        return $query->whereHas('products', function ($q) use ($startDate, $endDate) {
                            $q->whereHas('items', function ($itemQuery) use ($startDate, $endDate) {
                                $itemQuery->whereDoesntHave('detailTransactions.transaction', function ($transQuery) use ($startDate, $endDate) {
                                    $transQuery->where('booking_status', '!=', 'cancel')
                                        ->where(function ($q) use ($startDate, $endDate) {
                                            $q->whereBetween('start_date', [$startDate, $endDate])
                                                ->orWhereBetween('end_date', [$startDate, $endDate])
                                                ->orWhere(function ($q2) use ($startDate, $endDate) {
                                                    $q2->where('start_date', '<=', $startDate)
                                                        ->where('end_date', '>=', $endDate);
                                                });
                                        });
                                });
                            });
                        });
                    })
                    ->indicateUsing(function (array $data): ?string {
                        return (!empty($data['value']) && $data['value']) ? 'Only available bundlings' : null;
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
            ->emptyStateHeading('No bundlings found')
            ->emptyStateDescription('There are no bundlings in the system or none match your current filters.')
            ->emptyStateIcon('heroicon-o-cube');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBundlingAvailabilities::route('/'),
        ];
    }
}
