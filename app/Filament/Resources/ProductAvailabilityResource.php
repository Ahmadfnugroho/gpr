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
                            $startDate = request('start_date');
                            $endDate = request('end_date');

                            if (!$startDate || !$endDate) {
                                $startDate = now()->format('Y-m-d H:i:s');
                                $endDate = now()->addDays(7)->format('Y-m-d H:i:s');
                            }

                            return $record->getAvailableItemsForPeriod($startDate, $endDate);
                        } catch (\Exception $e) {
                            \Log::error('Error getting available items: ' . $e->getMessage());
                            return 0;
                        }
                    })
                    ->alignCenter()
                    ->color(function ($state) {
                        // Red if 0, green if > 0
                        return $state == 0 ? 'danger' : 'success';
                    })
                    ->weight(FontWeight::Bold),

                TextColumn::make('current_rentals')
                    ->label('Current Rentals')
                    ->getStateUsing(function ($record) {
                        try {
                            $startDate = request('start_date');
                            $endDate = request('end_date');

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

Action::make('edit_transaction')
                    ->label('Edit Transaction')
                    ->icon('heroicon-m-pencil-square')
                    ->url(function ($record) {
                        try {
                            // Get first active transaction for this item
                            if ($record->type === 'product' && $record->product_model) {
                                $transaction = \App\Models\Transaction::whereIn('booking_status', ['booking', 'paid', 'on_rented'])
                                    ->whereHas('detailTransactions.detailTransactionProductItems.productItem', function ($query) use ($record) {
                                        $query->where('product_id', $record->product_model->id);
                                    })
                                    ->orderBy('start_date', 'desc')
                                    ->first();
                            } elseif ($record->type === 'bundling' && $record->bundling_model) {
                                $transaction = \App\Models\Transaction::whereIn('booking_status', ['booking', 'paid', 'on_rented'])
                                    ->whereHas('detailTransactions', function ($query) use ($record) {
                                        $query->where('bundling_id', $record->bundling_model->id);
                                    })
                                    ->orderBy('start_date', 'desc')
                                    ->first();
                            }
                            
                            if ($transaction) {
                                return route('filament.admin.resources.transactions.edit', ['record' => $transaction->id]);
                            }
                            
                            return null;
                        } catch (\Exception $e) {
                            \Log::error('Error getting transaction for edit: ' . $e->getMessage());
                            return null;
                        }
                    })
                    ->openUrlInNewTab()
                    ->color('warning')
                    ->tooltip(function ($record) {
                        try {
                            // Show tooltip with transaction count
                            if ($record->type === 'product' && $record->product_model) {
                                $count = \App\Models\Transaction::whereIn('booking_status', ['booking', 'paid', 'on_rented'])
                                    ->whereHas('detailTransactions.detailTransactionProductItems.productItem', function ($query) use ($record) {
                                        $query->where('product_id', $record->product_model->id);
                                    })
                                    ->count();
                            } elseif ($record->type === 'bundling' && $record->bundling_model) {
                                $count = \App\Models\Transaction::whereIn('booking_status', ['booking', 'paid', 'on_rented'])
                                    ->whereHas('detailTransactions', function ($query) use ($record) {
                                        $query->where('bundling_id', $record->bundling_model->id);
                                    })
                                    ->count();
                            } else {
                                $count = 0;
                            }
                            
                            if ($count > 1) {
                                return "Opens most recent transaction. Total {$count} active transactions - see Current Rentals column for all transactions.";
                            }
                            
                            return "Edit the active transaction";
                        } catch (\Exception $e) {
                            return "Edit transaction";
                        }
                    })
                    ->visible(function ($record) {
                        try {
                            // Only show if there are active transactions
                            if ($record->type === 'product' && $record->product_model) {
                                $count = \App\Models\Transaction::whereIn('booking_status', ['booking', 'paid', 'on_rented'])
                                    ->whereHas('detailTransactions.detailTransactionProductItems.productItem', function ($query) use ($record) {
                                        $query->where('product_id', $record->product_model->id);
                                    })
                                    ->count();
                                return $count > 0;
                            } elseif ($record->type === 'bundling' && $record->bundling_model) {
                                $count = \App\Models\Transaction::whereIn('booking_status', ['booking', 'paid', 'on_rented'])
                                    ->whereHas('detailTransactions', function ($query) use ($record) {
                                        $query->where('bundling_id', $record->bundling_model->id);
                                    })
                                    ->count();
                                return $count > 0;
                            }
                            return false;
                        } catch (\Exception $e) {
                            return false;
                        }
                    }),

                Action::make('edit_item')
                    ->label('Edit Item')
                    ->icon('heroicon-m-cog-6-tooth')
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
                                    <li><span class="text-green-600">Green Available Items</span>: Items are available (>0)</li>
                                    <li><span class="text-red-600">Red Available Items</span>: No items available (0)</li>
                                    <li><strong>Current Rentals</strong>: Shows all active bookings (booking, paid, on_rented)</li>
                                    <li>Each transaction shows customer name and can be clicked to edit</li>
                                </ul>
                            </div>
                            <div>
                                <h3 class="font-semibold text-lg">🔗 Quick Actions:</h3>
                                <ul class="list-disc ml-6 space-y-2 mt-2">
                                    <li>Click customer names in Current Rentals to edit transactions directly</li>
                                    <li>Use "Edit Transaction" to select from multiple active transactions</li>
                                    <li>Use "View All Transactions" to see complete transaction list</li>
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
