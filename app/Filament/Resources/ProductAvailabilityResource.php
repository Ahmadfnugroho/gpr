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
use Filament\Forms\Components\Grid;
use Filament\Support\Enums\FontWeight;
use Illuminate\Support\HtmlString;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Collection;

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
                // This resource is read-only, no form needed
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
                    ->color(fn (string $state): string => match ($state) {
                        'product' => 'primary',
                        'bundling' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),

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
                            \Log::error('Error getting description: ' . $e->getMessage());
                            return 'Description unavailable';
                        }
                    }),

                TextColumn::make('total_items')
                    ->label('Total Items')
                    ->getStateUsing(function ($record) {
                        try {
                            return $record->getTotalItems();
                        } catch (\Exception $e) {
                            \Log::error('Error getting total items: ' . $e->getMessage());
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
                            \Log::error('Error getting available items: ' . $e->getMessage());
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

                TextColumn::make('period_status')
                    ->label('Period Status')
                    ->getStateUsing(function () {
                        $startDate = request('tableFilters.date_range.start_date');
                        $endDate = request('tableFilters.date_range.end_date');

                        if (!$startDate || !$endDate) {
                            $startDate = now()->format('Y-m-d');
                            $endDate = now()->addDays(7)->format('Y-m-d');
                        }

                        return Carbon::parse($startDate)->format('M d') . ' - ' . Carbon::parse($endDate)->format('M d, Y');
                    })
                    ->alignCenter()
                    ->color('gray')
                    ->size(TextColumn\TextColumnSize::ExtraSmall),
            ])
            ->filters([
                Filter::make('date_range')
                    ->form([
                        Grid::make(2)
                            ->schema([
                                DatePicker::make('start_date')
                                    ->label('Start Date')
                                    ->default(now())
                                    ->required(),
                                DatePicker::make('end_date')
                                    ->label('End Date')
                                    ->default(now()->addDays(7))
                                    ->required(),
                            ])
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        // For virtual model, we don't modify the query here
                        // The filtering is handled in the records method
                        return $query;
                    })
                    ->indicateUsing(function (array $data): ?string {
                        $start = $data['start_date'] ?? now()->format('Y-m-d');
                        $end = $data['end_date'] ?? now()->addDays(7)->format('Y-m-d');
                        
                        return 'Period: ' . Carbon::parse($start)->format('M d') . ' - ' . Carbon::parse($end)->format('M d, Y');
                    }),
            ])
            ->actions([
                Action::make('view_details')
                    ->label('View Details')
                    ->icon('heroicon-m-eye')
                    ->url(function ($record) {
                        if ($record->type === 'product') {
                            return route('filament.admin.resources.products.view', [
                                'record' => str_replace('product_', '', $record->id)
                            ]);
                        } elseif ($record->type === 'bundling') {
                            return route('filament.admin.resources.bundlings.view', [
                                'record' => str_replace('bundling_', '', $record->id)
                            ]);
                        }
                        return null;
                    })
                    ->openUrlInNewTab(),
            ])
            ->defaultSort('name', 'asc')
            ->searchable(false) // We'll handle search differently
            ->recordUrl(null); // Disable row click navigation
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
