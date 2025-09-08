<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BaseOptimizedResource;
use App\Filament\Resources\TransactionResource\Number;
use App\Filament\Resources\TransactionResource\Pages;
use App\Services\TransactionImportExportService;
use App\Services\ResourceCacheService;
use App\Repositories\TransactionRepository;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

use App\Models\Bundling;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\Customer;
use App\Models\CustomerPhoneNumber;
use App\Models\ProductItem;
use App\Models\DetailTransactionProductItem;
use Carbon\Carbon;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Components\CheckboxList;

use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Database\Eloquent\Collection;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\ActionSize;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextColumn\TextColumnSize;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Actions\Exports\Enums\ExportFormat;
use App\Filament\Exports\TransactionExporter;
use Illuminate\Support\Facades\DB;

use Filament\Tables\Columns\TextInputColumn;
use Rmsramos\Activitylog\Actions\ActivityLogTimelineTableAction;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section as InfoSection;
use Filament\Infolists\Components\Grid as InfoGrid;

/**
 * @property array $data
 */
class TransactionResource extends BaseOptimizedResource
{
    protected static ?string $model = Transaction::class;
    protected static ?string $recordTitleAttribute = 'booking_transaction_id';

    /**
     * Repository instance for optimized data access
     */
    protected static ?TransactionRepository $repository = null;

    /**
     * Get repository instance
     */
    protected static function getRepository(): TransactionRepository
    {
        if (static::$repository === null) {
            static::$repository = new TransactionRepository(new Transaction());
        }

        return static::$repository;
    }

    /**
     * Get columns to select for optimized queries
     */
    protected static function getSelectColumns(): array
    {
        return [
            'transactions.id',
            'transactions.booking_transaction_id',
            'transactions.customer_id',
            'transactions.start_date',
            'transactions.end_date',
            'transactions.grand_total',
            'transactions.booking_status',
            'transactions.promo_id',
            'transactions.created_at',
            'transactions.updated_at'
        ];
    }

    /**
     * Get relationships to eager load
     */
    protected static function getEagerLoadRelations(): array
    {
        return [
            'customer:id,name,email',
            'customer.customerPhoneNumbers:id,customer_id,phone_number',
            'detailTransactions:id,transaction_id,product_id,bundling_id,quantity',
            'detailTransactions.product:id,name,price',
            'detailTransactions.bundling:id,name,price',
            'detailTransactions.bundling.products:id,name',
            'detailTransactions.productItems:id,serial_number,product_id',
            'promo:id,name,type,rules'
        ];
    }

    // Global Search disabled for TransactionResource
    public static function getGloballySearchableAttributes(): array
    {
        return [];
    }

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationLabel = 'Transactions';
    protected static ?int $navigationSort = 31;

    /**
     * Highlight search terms in text
     */
    protected static function highlightSearchTerm(?string $text, ?string $searchTerm): string
    {
        if (!$text || !$searchTerm || strlen($searchTerm) < 2) {
            return $text ?? '';
        }

        $highlighted = preg_replace(
            '/(' . preg_quote($searchTerm, '/') . ')/i',
            '<mark style="background-color: yellow; padding: 2px;">$1</mark>',
            $text
        );

        return $highlighted ?? $text;
    }
    protected static function resolveAvailableProductSerials(Get $get, $set = null): array
    {
        $productId = $get('product_id');
        $quantity = max(1, (int) $get('quantity'));
        $currentUuid = $get('uuid');

        // Generate UUID if missing
        if (!$currentUuid && $set) {
            $currentUuid = (string) \Illuminate\Support\Str::uuid();
            $set('uuid', $currentUuid);
        }

        $startDate = $get('../../start_date') ? Carbon::parse($get('../../start_date')) : now();
        $endDate = $get('../../end_date') ? Carbon::parse($get('../../end_date')) : now();
        $allDetailTransactions = $get('../../detailTransactions') ?? [];
        $currentTransactionId = $get('../../id'); // Get current transaction ID when editing

        if (!$productId || !$currentUuid) {
            return [];
        }

        // Ambil product_item_id dari tabel pivot yang sudah digunakan dalam repeater saat ini
        $usedInCurrentRepeater = collect($allDetailTransactions)
            ->filter(fn($row) => is_array($row) && isset($row['uuid']) && $row['uuid'] !== $currentUuid)
            ->flatMap(fn($row) => is_array($row['productItems'] ?? null) ? $row['productItems'] : [])
            ->unique()
            ->values()
            ->all();


        // Ambil product_item_id dari transaksi lain yang aktif (EXCLUDE current transaction when editing)
        $usedInOtherTransactions = DetailTransactionProductItem::whereHas('detailTransaction.transaction', function ($query) use ($startDate, $endDate, $currentTransactionId) {
            $query->where(function ($q) use ($startDate, $endDate) {
                // Periksa apakah rentang tanggal tumpang tindih
                $q->whereBetween('start_date', [$startDate, $endDate]) // Rentang mulai di dalam rentang target
                    ->orWhereBetween('end_date', [$startDate, $endDate]) // Rentang akhir di dalam rentang target
                    ->orWhere(function ($q2) use ($startDate, $endDate) {
                        // Rentang target sepenuhnya berada di dalam rentang transaksi
                        $q2->where('start_date', '<=', $startDate)
                            ->where('end_date', '>=', $endDate);
                    });
            })
                // EXCLUDE current transaction when editing to avoid false "unavailable" status
                ->when($currentTransactionId, function ($q) use ($currentTransactionId) {
                    $q->where('id', '!=', $currentTransactionId);
                });
        })
            ->pluck('product_item_id')
            ->toArray();


        // Gabungkan semua ID yang harus dikeluarkan
        $excludedIds = array_unique(array_merge($usedInCurrentRepeater, $usedInOtherTransactions));


        // Get currently assigned items to this detail transaction (for editing)
        $currentlyAssignedIds = [];
        $currentDetailTransactionId = $get('id');
        if ($currentDetailTransactionId) {
            $currentlyAssignedIds = DetailTransactionProductItem::where('detail_transaction_id', $currentDetailTransactionId)
                ->whereHas('productItem', function ($q) use ($productId) {
                    $q->where('product_id', $productId);
                })
                ->pluck('product_item_id')
                ->toArray();
        }

        // Ambil serial number yang tersedia (including currently assigned ones for editing)
        $availableIds = ProductItem::query()
            ->where('product_id', $productId)
            ->where(function ($query) use ($excludedIds, $currentlyAssignedIds) {
                // Include items that are not excluded OR currently assigned to this transaction
                $query->whereNotIn('id', $excludedIds)
                    ->orWhereIn('id', $currentlyAssignedIds);
            })
            ->limit($quantity + count($currentlyAssignedIds)) // Allow more to include currently assigned
            ->pluck('id')
            ->toArray();

        return $availableIds;
    }
    public static function resolveBundlingProductSerialsDisplay(
        int $bundlingId,
        int $quantity,
        Carbon $startDate,
        Carbon $endDate,
        ?string $currentUuid,
        array $allDetailTransactions,
        ?int $currentTransactionId = null,
        ?int $currentDetailTransactionId = null
    ): array {

        $bundling = Bundling::with('products')->find($bundlingId);
        if (!$bundling) {
            return ['ids' => [], 'display' => '-'];
        }

        // Generate UUID if missing
        if (!$currentUuid) {
            $currentUuid = (string) \Illuminate\Support\Str::uuid();
        }

        // Ambil product_item_id dari tabel pivot yang sudah digunakan dalam repeater saat ini
        $usedInCurrentRepeater = collect($allDetailTransactions)
            ->filter(fn($row) => is_array($row) && isset($row['uuid']) && $row['uuid'] !== $currentUuid)
            ->flatMap(fn($row) => is_array($row['productItems'] ?? null) ? $row['productItems'] : [])
            ->unique()
            ->values()
            ->all();


        // Ambil product_item_id dari transaksi lain yang aktif (EXCLUDE current transaction when editing)
        $usedInOtherTransactions = DetailTransactionProductItem::whereHas('detailTransaction.transaction', function ($query) use ($startDate, $endDate, $currentTransactionId) {
            $query->where(function ($q) use ($startDate, $endDate) {
                // Periksa apakah rentang tanggal tumpang tindih
                $q->whereBetween('start_date', [$startDate, $endDate]) // Rentang mulai di dalam rentang target
                    ->orWhereBetween('end_date', [$startDate, $endDate]) // Rentang akhir di dalam rentang target
                    ->orWhere(function ($q2) use ($startDate, $endDate) {
                        // Rentang target sepenuhnya berada di dalam rentang transaksi
                        $q2->where('start_date', '<=', $startDate)
                            ->where('end_date', '>=', $endDate);
                    });
            })
                // EXCLUDE current transaction when editing to avoid false "unavailable" status
                ->when($currentTransactionId, function ($q) use ($currentTransactionId) {
                    $q->where('id', '!=', $currentTransactionId);
                });
        })
            ->pluck('product_item_id')
            ->toArray();


        // Gabungkan semua ID yang harus dikeluarkan
        $excludedIds = array_unique(array_merge($usedInCurrentRepeater, $usedInOtherTransactions));



        $resultIds = [];
        $displayParts = [];

        foreach ($bundling->products as $product) {
            $requiredQty = $quantity * ($product->pivot->quantity ?? 1);

            // Get currently assigned items for this product in this detail transaction
            $currentlyAssignedIds = [];
            if ($currentDetailTransactionId) {
                $currentlyAssignedIds = DetailTransactionProductItem::where('detail_transaction_id', $currentDetailTransactionId)
                    ->whereHas('productItem', function ($q) use ($product) {
                        $q->where('product_id', $product->id);
                    })
                    ->pluck('product_item_id')
                    ->toArray();
            }

            // Ambil serial number yang tersedia untuk produk ini (including currently assigned ones)
            $items = ProductItem::query()
                ->where('product_id', $product->id)
                ->where(function ($query) use ($excludedIds, $currentlyAssignedIds) {
                    // Include items that are not excluded OR currently assigned to this transaction
                    $query->whereNotIn('id', $excludedIds)
                        ->orWhereIn('id', $currentlyAssignedIds);
                })
                ->limit($requiredQty + count($currentlyAssignedIds))
                ->get(['id', 'serial_number']);

            $ids = $items->pluck('id')->toArray();
            $serials = $items->pluck('serial_number')->toArray();

            if (!empty($serials)) {
                $displayParts[] = "{$product->name} (" . implode(', ', $serials) . ")";
            }

            $resultIds = array_merge($resultIds, $ids);
        }

        $finalDisplay = empty($displayParts) ? '-' : implode(', ', $displayParts);


        return [
            'ids' => $resultIds,
            'display' => $finalDisplay,
        ];
    }
    public static function resolveProductOrBundlingSelection($state, $set, $get, $allDetailTransactions)
    {
        if (!$state) {
            $set('is_bundling', false);
            $set('bundling_id', null);
            $set('product_id', null);
            $set('productItems', []); // Reset productItems jika state kosong

            return;
        }

        // Reset quantity awal
        $set('quantity', 1);

        // Pisahkan type dan ID dari state dengan validasi
        if (!str_contains($state, '-')) {
            return; // Invalid format
        }

        [$type, $id] = explode('-', $state, 2);
        if (!$type || !$id) {
            return; // Invalid parts
        }

        $isBundling = $type === 'bundling';
        $set('is_bundling', $isBundling);

        // Ambil data tambahan dengan null safety
        $uuid = $get('uuid');
        if (!$uuid) {
            $uuid = (string) \Illuminate\Support\Str::uuid();
            $set('uuid', $uuid);
        }

        $startDate = $get('../../start_date') ? Carbon::parse($get('../../start_date')) : now();
        $endDate = $get('../../end_date') ? Carbon::parse($get('../../end_date')) : now();
        $currentTransactionId = $get('../../id'); // Get current transaction ID when editing
        $currentDetailTransactionId = $get('id'); // Get current detail transaction ID when editing

        if ($isBundling) {
            // Jika bundling dipilih
            $set('bundling_id', (int) $id);
            $set('product_id', null);

            // Resolve serial numbers untuk bundling
            $result = \App\Filament\Resources\TransactionResource::resolveBundlingProductSerialsDisplay(
                (int) $id,
                1, // Quantity default
                $startDate,
                $endDate,
                $uuid,
                $allDetailTransactions ?? [],
                $currentTransactionId,
                $currentDetailTransactionId
            );

            // Auto-assign first available items based on quantity
            $resultIds = $result['ids'] ?? [];
            $quantity = $get('quantity') ?? 1;
            $assignedIds = array_slice($resultIds, 0, $quantity);
            $set('productItems', $assignedIds);
        } else {
            // Jika produk tunggal dipilih
            $set('product_id', (int) $id);
            $set('bundling_id', null);

            // Resolve serial numbers untuk produk tunggal dan auto-assign
            $serials = \App\Filament\Resources\TransactionResource::resolveAvailableProductSerials($get, $set);
            $quantity = $get('quantity') ?? 1;
            $assignedSerials = array_slice($serials ?? [], 0, $quantity);

            $set('productItems', $assignedSerials); // Auto-assign based on quantity
        }
    }


    /**
     * Calculate total price before discount from detail transactions
     */
    protected static function calculateTotalBeforeDiscount(Get $get): int
    {
        $details = collect($get('detailTransactions') ?? []);

        return $details->sum(function ($item) {
            // Skip if not array
            if (!is_array($item)) {
                return 0;
            }

            $quantity = (int)($item['quantity'] ?? 1);

            // Ensure quantity is at least 1
            if ($quantity < 1) $quantity = 1;

            $price = 0;

            if (!empty($item['is_bundling']) && !empty($item['bundling_id'])) {
                $bundling = \App\Models\Bundling::find($item['bundling_id']);
                $price = $bundling ? (int)$bundling->price : 0;
            } elseif (!empty($item['product_id'])) {
                $product = \App\Models\Product::find($item['product_id']);
                $price = $product ? (int)$product->price : 0;
            }

            return $price * $quantity;
        });
    }

    /**
     * Calculate discount amount based on promo rules
     */
    protected static function calculateDiscountAmount(Get $get, int $totalBeforeDiscount): int
    {
        $promoId = $get('promo_id');
        if (!$promoId) return 0;

        $promo = \App\Models\Promo::find($promoId);
        if (!$promo || !is_array($promo->rules) || empty($promo->rules)) {
            return 0;
        }

        $rule = $promo->rules[0] ?? [];
        $duration = (int)($get('duration') ?? 1);

        // Apply discount per duration (multiply by duration)
        $totalWithDuration = $totalBeforeDiscount * $duration;

        $discount = match ($promo->type) {
            'day_based' => static::calculateDayBasedDiscount($rule, $duration, $totalBeforeDiscount),
            'percentage' => static::calculatePercentageDiscount($rule, $totalWithDuration),
            'nominal' => static::calculateNominalDiscount($rule, $totalWithDuration),
            default => 0,
        };

        return max(0, min($discount, $totalWithDuration));
    }

    /**
     * Calculate day-based discount
     */
    protected static function calculateDayBasedDiscount(array $rule, int $duration, int $totalBeforeDiscount): int
    {
        $groupSize = max(1, (int)($rule['group_size'] ?? 1));
        $payDays = max(0, (int)($rule['pay_days'] ?? 0));

        $fullGroups = intval($duration / $groupSize);
        $remainingDays = $duration % $groupSize;

        $discountedDays = $fullGroups * $payDays;
        $totalDaysToPay = $discountedDays + $remainingDays;

        $totalWithoutDiscount = $totalBeforeDiscount * $duration;
        $totalWithDiscount = $totalBeforeDiscount * $totalDaysToPay;

        return max(0, $totalWithoutDiscount - $totalWithDiscount);
    }

    /**
     * Calculate percentage discount
     */
    protected static function calculatePercentageDiscount(array $rule, int $totalWithDuration): int
    {
        $percentage = max(0, min(100, (float)($rule['percentage'] ?? 0)));
        return (int)(($totalWithDuration * $percentage) / 100);
    }

    /**
     * Calculate nominal discount
     */
    protected static function calculateNominalDiscount(array $rule, int $totalWithDuration): int
    {
        $nominal = max(0, (int)($rule['nominal'] ?? 0));
        return min($nominal, $totalWithDuration);
    }

    /**
     * Calculate grand total (total before discount * duration - discount + additional fees)
     */
    protected static function calculateGrandTotal(Get $get): int
    {
        $duration = max(1, (int)($get('duration') ?? 1));
        $totalBeforeDiscount = static::calculateTotalBeforeDiscount($get);

        if ($totalBeforeDiscount <= 0) {
            return 0;
        }

        // Apply duration to base price
        $totalWithDuration = $totalBeforeDiscount * $duration;

        // Calculate discount based on total with duration
        $discountAmount = static::calculateDiscountAmount($get, $totalBeforeDiscount);

        // Calculate additional services fees from repeater
        $additionalFees = 0;
        $additionalServices = $get('additional_services') ?? [];
        if (is_array($additionalServices)) {
            foreach ($additionalServices as $service) {
                if (is_array($service) && isset($service['amount'])) {
                    $additionalFees += (int)($service['amount'] ?? 0);
                }
            }
        }

        // Legacy support for old structure
        $additionalFees += (int)($get('additional_fee_1_amount') ?? 0);
        $additionalFees += (int)($get('additional_fee_2_amount') ?? 0);
        $additionalFees += (int)($get('additional_fee_3_amount') ?? 0);

        // Final total: (base price * duration) - discount + additional fees
        return max(0, $totalWithDuration - $discountAmount + $additionalFees);
    }

    protected static function getGrandTotalValue(Get $get): int
    {
        return static::calculateGrandTotal($get);
    }

    /**
     * Update booking status based on payment amounts
     */
    protected static function updateBookingStatusBasedOnPayment(Set $set, int $downPayment, int $grandTotal): void
    {
        if ($grandTotal <= 0) {
            $set('booking_status', 'cancel');
            return;
        }

        if ($downPayment <= 0) {
            $set('booking_status', 'cancel');
        } elseif ($downPayment >= $grandTotal) {
            // Full payment - keep existing status if it's on_rented/done, otherwise set to paid
            $currentStatus = request()->input('booking_status');
            if (!in_array($currentStatus, ['on_rented', 'done'])) {
                $set('booking_status', 'paid');
            }
        } else {
            // Partial payment
            $minPayment = max(0, floor($grandTotal * 0.5));
            if ($downPayment >= $minPayment) {
                $set('booking_status', 'booking');
            } else {
                $set('booking_status', 'cancel');
            }
        }
    }

    /**
     * Validate product availability for the given date range
     */
    protected static function validateProductAvailability(Get $get, Carbon $startDate, Carbon $endDate): void
    {
        $detailTransactions = $get('detailTransactions') ?? [];

        foreach ($detailTransactions as $detailTransaction) {
            // Skip if not array or no product selected
            if (!is_array($detailTransaction) || empty($detailTransaction['selection_key'])) {
                continue;
            }

            $selectionKey = $detailTransaction['selection_key'];
            $quantity = (int)($detailTransaction['quantity'] ?? 1);

            if (str_starts_with($selectionKey, 'bundling-')) {
                $bundlingId = (int)substr($selectionKey, 9);
                $bundling = \App\Models\Bundling::find($bundlingId);

                if ($bundling) {
                    $available = $bundling->getAvailableQuantityForPeriod($startDate, $endDate, $quantity);

                    if ($available <= 0) {
                        Notification::make()
                            ->warning()
                            ->title('Bundling Availability Issue')
                            ->body("Bundling '{$bundling->name}' may not be available for the selected date range.")
                            ->send();
                    }
                }
            } elseif (str_starts_with($selectionKey, 'produk-')) {
                $productId = (int)substr($selectionKey, 7);
                $product = \App\Models\Product::find($productId);

                if ($product) {
                    $available = $product->getAvailableQuantityForPeriod($startDate, $endDate);

                    if ($available < $quantity) {
                        Notification::make()
                            ->warning()
                            ->title('Product Availability Issue')
                            ->body("Product '{$product->name}' may not have sufficient quantity available for the selected date range.")
                            ->send();
                    }
                }
            }
        }
    }

    public static function form(Form $form): Form
    {

        return $form->schema([

            TextInput::make('booking_transaction_id')
                ->label('Booking Transaction ID')
                ->disabled()
                ->extraAttributes([
                    'aria-label' => 'Booking Transaction ID (auto-generated)',
                    'id' => 'booking_transaction_id'
                ])
                ->columnSpanFull(),
            Grid::make('Durasi')
                ->schema([
                    Select::make('customer_id')
                        ->relationship('customer', 'name', fn(Builder $query) => $query->where('status', Customer::STATUS_ACTIVE))
                        ->required()
                        ->searchable()
                        ->preload()
                        ->live()
                        ->columnSpan(1)
                        ->extraAttributes([
                            'aria-label' => 'Select customer for this transaction',
                            'aria-describedby' => 'customer-selection-help',
                            'id' => 'customer_id_select'
                        ])
                        ->afterStateUpdated(function ($state, callable $set) {
                            $set('customer_id', $state);
                            $customer = \App\Models\Customer::find($state);
                            $set('customer_status', $customer ? $customer->status : '');
                            $set('customer_email', $customer ? $customer->email : '');
                            $phoneNumber = \App\Models\CustomerPhoneNumber::where('customer_id', $state)->first();
                            $set('customer_phone_number', $phoneNumber ? $phoneNumber->phone_number : '');
                        })
                        ->helperText(function (Get $get) {
                            $customer = Customer::find($get('customer_id'));

                            if (! $customer) {
                                return new HtmlString('Pilih customer untuk melihat detail.');
                            }

                            $statusColor = match ($customer->status) {
                                Customer::STATUS_ACTIVE => 'text-green-600',
                                Customer::STATUS_BLACKLIST => 'text-red-600',
                                default => 'text-gray-600',
                            };

                            return new HtmlString(
                                "Status: <strong class=\"{$statusColor}\">{$customer->status}</strong><br>" .
                                    "Email: <strong>{$customer->email}</strong><br>" .
                                    "Phone: <strong>{$customer->phone_number}</strong>"
                            );
                        }),

                    DateTimePicker::make('start_date')
                        ->label('Start Date')
                        ->seconds(false)
                        ->native(false)
                        ->displayFormat('d M Y, H:i')
                        ->format('d M Y, H:i')
                        ->required()
                        ->reactive()
                        ->default(now())
                        ->minDate(now()->subWeek())
                        ->extraAttributes([
                            'aria-label' => 'Select rental start date and time',
                            'aria-describedby' => 'start-date-help',
                            'id' => 'start_date_picker'
                        ])
                        ->afterStateUpdated(function ($state, $get, $set) {
                            $set('is_bundling', false);
                            $set('bundling_id', null);
                            $set('product_id', null);
                            $set('productItems', []); // 
                            $startDate = Carbon::parse($state)->format('Y-m-d H:i:s');
                            $duration = (int) $get('duration');

                            if ($startDate && $duration) {
                                // Calculate end date as hours (24 hours * duration)
                                $endDate = Carbon::parse($startDate)->addHours($duration * 24)->format('Y-m-d H:i');
                                $set('end_date', $endDate);
                            }
                            $allDetailTransactions = $get('../../detailTransactions') ?? [];
                            \App\Filament\Resources\TransactionResource::resolveProductOrBundlingSelection($state, $set, $get, $allDetailTransactions);
                        }),
                    Select::make('duration')
                        ->label('Duration')
                        ->required()
                        ->default(1)
                        ->options(array_combine(range(1, 30), range(1, 30)))
                        ->searchable()
                        ->suffix('Hari')
                        ->reactive()
                        ->extraAttributes([
                            'aria-label' => 'Select rental duration in days',
                            'aria-describedby' => 'duration-help',
                            'id' => 'duration_select'
                        ])
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            $startDate = $get('start_date');
                            $duration = (int) $state;
                            if ($startDate && $duration) {
                                // Calculate end date as hours (24 hours * duration)
                                $endDate = Carbon::parse($startDate)->addHours($duration * 24)->format('Y-m-d H:i:s');
                                $set('end_date', $endDate);
                            }
                            $allDetailTransactions = $get('../../detailTransactions') ?? [];
                            \App\Filament\Resources\TransactionResource::resolveProductOrBundlingSelection($state, $set, $get, $allDetailTransactions);
                        }),
                    DateTimePicker::make('end_date')
                        ->label('End Date')
                        ->seconds(false)
                        ->native(false)
                        ->displayFormat('d M Y, H:i')
                        ->format('d M Y, H:i')
                        ->reactive()
                        ->default(function (Get $get) {
                            $startDate = $get('start_date');
                            $duration = (int)($get('duration') ?? 1);
                            if ($startDate && $duration) {
                                return Carbon::parse($startDate)->addHours($duration * 24)->format('Y-m-d H:i');
                            }
                            return null;
                        })
                        ->extraAttributes([
                            'aria-label' => 'Rental end date and time (auto-calculated)',
                            'aria-describedby' => 'end-date-help',
                            'id' => 'end_date_picker'
                        ])
                        ->helperText(function (Get $get) {
                            $startDate = $get('start_date');
                            $duration = (int)($get('duration') ?? 1);

                            if (!$startDate) {
                                return 'Set start date first to see auto-calculated end date.';
                            }

                            try {
                                $autoEndDate = Carbon::parse($startDate)->addHours($duration * 24);
                                return 'Auto-calculated: ' . $autoEndDate->format('d M Y, H:i') . ' (' . ($duration * 24) . ' hours)';
                            } catch (\Exception $e) {
                                return 'Invalid start date format.';
                            }
                        })
                        ->afterStateUpdated(function ($state, Get $get, Set $set) {
                            if (!$state || !$get('start_date')) return;

                            try {
                                $startDate = Carbon::parse($get('start_date'));
                                $endDate = Carbon::parse($state);

                                // Calculate new duration based on manual end date
                                $newDuration = $startDate->diffInDays($endDate) + 1;

                                if ($newDuration < 1) {
                                    Notification::make()
                                        ->warning()
                                        ->title('Invalid Date Range')
                                        ->body('End date must be after start date.')
                                        ->send();
                                    return;
                                }

                                // Update duration based on manual end date
                                $set('duration', $newDuration);

                                // Validate product availability for new date range
                                static::validateProductAvailability($get, $startDate, $endDate);
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->danger()
                                    ->title('Invalid Date')
                                    ->body('Please enter a valid end date.')
                                    ->send();
                            }
                        })
                ])
                ->columns([
                    'sm' => 1,
                    'md' => 2,
                    'lg' => 4,
                ]),


            Grid::make('Detail Transaksi')
                ->schema([
                    Repeater::make('detailTransactions')
                        ->relationship()
                        ->schema([
                            Hidden::make('uuid')
                                ->label('UUID')
                                ->default(fn() => (string) Str::uuid()),
                            Hidden::make('is_bundling')
                                ->default(false)
                                ->afterStateHydrated(function ($state, $set, $get) {
                                    // Ensure is_bundling is set correctly based on existing data
                                    $productId = $get('product_id');
                                    $bundlingId = $get('bundling_id');

                                    if ($bundlingId && !$productId) {
                                        $set('is_bundling', true);
                                    } elseif ($productId && !$bundlingId) {
                                        $set('is_bundling', false);
                                    }
                                }),
                            Hidden::make('bundling_id')
                                ->afterStateHydrated(function ($state, $set, $get) {
                                    // Ensure bundling_id is preserved during edit
                                    if ($state) {
                                        $set('is_bundling', true);
                                    }
                                }),
                            Hidden::make('product_id')
                                ->afterStateHydrated(function ($state, $set, $get) {
                                    // Ensure product_id is preserved during edit
                                    if ($state && !$get('bundling_id')) {
                                        $set('is_bundling', false);
                                    }
                                }),
                            Grid::make()
                                ->columns([
                                    'sm' => 1,
                                    'md' => 2,
                                    'lg' => 6,
                                ])->schema([
                                    Select::make('selection_key')
                                        ->label(function (Get $get) {
                                            // Show current selection in label when editing
                                            $productId = $get('product_id');
                                            $bundlingId = $get('bundling_id');
                                            $isBundling = $get('is_bundling');

                                            if ($productId && !$isBundling) {
                                                $product = Product::find($productId);
                                                return $product ? "Pilih Produk/Bundling (Current: {$product->name})" : 'Pilih Produk/Bundling';
                                            } elseif ($bundlingId && $isBundling) {
                                                $bundling = Bundling::find($bundlingId);
                                                return $bundling ? "Pilih Produk/Bundling (Current: {$bundling->name} - Bundle)" : 'Pilih Produk/Bundling';
                                            }

                                            return 'Pilih Produk/Bundling';
                                        })
                                        ->searchable()
                                        ->options(function () {
                                            $products = Product::pluck('name', 'id')->mapWithKeys(fn($name, $id) => ["produk-{$id}" => $name]);
                                            $bundlings = Bundling::pluck('name', 'id')->mapWithKeys(fn($name, $id) => ["bundling-{$id}" => $name]);
                                            return $products->merge($bundlings)->toArray();
                                        })
                                        ->live(debounce: 300)
                                        ->extraAttributes([
                                            'aria-label' => 'Select product or bundling package for rental',
                                            'aria-describedby' => 'product-selection-help'
                                        ])
                                        ->default(function (Get $get) {
                                            // Pre-populate selection_key based on existing data when editing
                                            $productId = $get('product_id');
                                            $bundlingId = $get('bundling_id');
                                            $isBundling = $get('is_bundling');

                                            // Check if we have product_id (individual product)
                                            if ($productId && !$bundlingId) {
                                                return "produk-{$productId}";
                                            }
                                            // Check if we have bundling_id (bundling)
                                            elseif ($bundlingId && !$productId) {
                                                return "bundling-{$bundlingId}";
                                            }
                                            // Legacy support for is_bundling field
                                            elseif ($productId && !$isBundling) {
                                                return "produk-{$productId}";
                                            } elseif ($bundlingId && $isBundling) {
                                                return "bundling-{$bundlingId}";
                                            }

                                            return null;
                                        })

                                        // Trigger saat awal data dimuat
                                        ->afterStateHydrated(function ($state, Set $set, Get $get) {
                                            // If no state but we have product_id or bundling_id, construct the state
                                            if (!$state) {
                                                $productId = $get('product_id');
                                                $bundlingId = $get('bundling_id');

                                                if ($productId && !$bundlingId) {
                                                    $state = "produk-{$productId}";
                                                    $set('selection_key', $state);
                                                } elseif ($bundlingId && !$productId) {
                                                    $state = "bundling-{$bundlingId}";
                                                    $set('selection_key', $state);
                                                } else {
                                                    return; // No valid state to work with
                                                }
                                            }

                                            // Parse the state
                                            if (!str_contains($state, '-')) {
                                                return; // Invalid format
                                            }

                                            [$type, $id] = explode('-', $state, 2);
                                            if (!$type || !$id) {
                                                return; // Invalid parts
                                            }

                                            $isBundling = $type === 'bundling';
                                            $set('is_bundling', $isBundling);

                                            $uuid = $get('uuid');
                                            $startDate = $get('../../start_date') ? Carbon::parse($get('../../start_date')) : now();
                                            $endDate = $get('../../end_date') ? Carbon::parse($get('../../end_date')) : now();
                                            $all = $get('../../detailTransactions') ?? [];
                                            $currentTransactionId = $get('../../id'); // Get current transaction ID when editing

                                            if ($isBundling) {
                                                $set('bundling_id', (int) $id);
                                                $set('product_id', null);

                                                // Defer setting productItems to next tick to avoid premature access
                                                $result = \App\Filament\Resources\TransactionResource::resolveBundlingProductSerialsDisplay(
                                                    (int) $id,
                                                    $get('quantity') ?? 1,
                                                    $startDate,
                                                    $endDate,
                                                    $uuid,
                                                    $all,
                                                    $currentTransactionId
                                                );

                                                // Use dispatch after state hydrated to set productItems
                                                $set('productItems', $result['ids']);
                                            } else {
                                                $set('product_id', (int) $id);
                                                $set('bundling_id', null);

                                                $serials = \App\Filament\Resources\TransactionResource::resolveAvailableProductSerials($get, $set);
                                                $set('productItems', $serials);
                                            }
                                        })

                                        // Trigger saat user update manual
                                        ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                            if (!$state) {
                                                $set('is_bundling', false);
                                                $set('bundling_id', null);
                                                $set('product_id', null);
                                                $set('productItems', []); // Reset productItems jika state kosong

                                                return;
                                            }

                                            $set('quantity', 1); // reset quantity awal

                                            [$type, $id] = explode('-', $state);
                                            $isBundling = $type === 'bundling';
                                            $set('is_bundling', $isBundling);

                                            $allDetailTransactions = $get('../../detailTransactions') ?? [];
                                            \App\Filament\Resources\TransactionResource::resolveProductOrBundlingSelection($state, $set, $get, $allDetailTransactions);
                                        })
                                        // Prevent selection_key from being saved to database
                                        ->dehydrated(false)
                                        ->columnSpan(2),
                                    TextInput::make('quantity')
                                        ->label('Jumlah')
                                        ->numeric()
                                        ->default(1)
                                        ->minValue(1)
                                        ->live(debounce: 300)
                                        ->extraAttributes([
                                            'aria-label' => 'Enter quantity of items to rent',
                                            'aria-describedby' => 'quantity-help',
                                            'min' => '1',
                                            'step' => '1'
                                        ])
                                        ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                            $allDetailTransactions = $get('../../detailTransactions') ?? [];
                                            \App\Filament\Resources\TransactionResource::resolveProductOrBundlingSelection($state, $set, $get, $allDetailTransactions);
                                        })
                                        ->columnSpan(1),

                                    Placeholder::make('serial_numbers_display')
                                        ->label(function (Get $get) {
                                            $startDate = $get('../../start_date') ? \Carbon\Carbon::parse($get('../../start_date')) : now();
                                            $endDate = $get('../../end_date') ? \Carbon\Carbon::parse($get('../../end_date')) : now();
                                            $productId = $get('product_id');
                                            $bundlingId = $get('bundling_id');
                                            $quantity = (int) ($get('quantity') ?? 1);

                                            $availableCount = 0;
                                            if ($productId) {
                                                $available = \App\Filament\Resources\TransactionResource::resolveAvailableProductSerials($get);
                                                $availableCount = count($available);
                                            } elseif ($bundlingId) {
                                                $currentTransactionId = $get('../../id');
                                                $currentDetailTransactionId = $get('id');
                                                $result = \App\Filament\Resources\TransactionResource::resolveBundlingProductSerialsDisplay(
                                                    (int) $bundlingId,
                                                    $quantity,
                                                    $startDate,
                                                    $endDate,
                                                    $get('uuid'),
                                                    $get('../../detailTransactions') ?? [],
                                                    $currentTransactionId,
                                                    $currentDetailTransactionId
                                                );
                                                $availableCount = count($result['ids']);
                                            }

                                            $status = $availableCount >= $quantity ? '✅ Tersedia' : '⚠️ Tidak Tersedia';

                                            return "Serial Numbers Auto-Assigned ({$availableCount} tersedia) - {$status}";
                                        })
                                        ->content(function (Get $get) {
                                            $productId = $get('product_id');
                                            $bundlingId = $get('bundling_id');
                                            $quantity = (int) ($get('quantity') ?? 1);

                                            if (!$productId && !$bundlingId) {
                                                return new HtmlString('Pilih produk atau bundling terlebih dahulu');
                                            }

                                            // Get assigned serial numbers display
                                            $detailTransactionId = $get('id');
                                            if ($detailTransactionId) {
                                                // Edit mode - show currently assigned serial numbers
                                                $detailTransaction = \App\Models\DetailTransaction::with('productItems.product')->find($detailTransactionId);
                                                if ($detailTransaction && $detailTransaction->productItems->isNotEmpty()) {
                                                    $serialNumbers = $detailTransaction->productItems->pluck('serial_number')->toArray();
                                                    return new HtmlString('<strong>Assigned:</strong> ' . implode(', ', $serialNumbers));
                                                }
                                            }

                                            // New transaction - show what will be assigned
                                            if ($productId) {
                                                $available = \App\Filament\Resources\TransactionResource::resolveAvailableProductSerials($get);
                                                if (!empty($available)) {
                                                    $items = \App\Models\ProductItem::whereIn('id', array_slice($available, 0, $quantity))->pluck('serial_number')->toArray();
                                                    return new HtmlString('<strong>Will be assigned:</strong> ' . (empty($items) ? 'None available' : implode(', ', $items)));
                                                }
                                            } elseif ($bundlingId) {
                                                $currentTransactionId = $get('../../id');
                                                $currentDetailTransactionId = $get('id');
                                                $startDate = $get('../../start_date') ? \Carbon\Carbon::parse($get('../../start_date')) : now();
                                                $endDate = $get('../../end_date') ? \Carbon\Carbon::parse($get('../../end_date')) : now();
                                                $result = \App\Filament\Resources\TransactionResource::resolveBundlingProductSerialsDisplay(
                                                    (int) $bundlingId,
                                                    $quantity,
                                                    $startDate,
                                                    $endDate,
                                                    $get('uuid'),
                                                    $get('../../detailTransactions') ?? [],
                                                    $currentTransactionId,
                                                    $currentDetailTransactionId
                                                );
                                                return new HtmlString('<strong>Will be assigned:</strong> ' . ($result['display'] ?: 'None available'));
                                            }

                                            return new HtmlString('No items available');
                                        })
                                        ->visible(function (Get $get) {
                                            return !is_null($get('selection_key'));
                                        }),

                                    // Hidden field to store auto-assigned productItems
                                    Hidden::make('productItems')
                                        ->default(function (Get $get) {
                                            // For edit mode, load existing productItems from database relationship
                                            $detailTransactionId = $get('id');
                                            if ($detailTransactionId) {
                                                $detailTransaction = \App\Models\DetailTransaction::with('productItems')->find($detailTransactionId);
                                                if ($detailTransaction && $detailTransaction->productItems->isNotEmpty()) {
                                                    return $detailTransaction->productItems->pluck('id')->toArray();
                                                }
                                            }

                                            // For new records, auto-assign available items
                                            $productId = $get('product_id');
                                            $bundlingId = $get('bundling_id');
                                            $quantity = (int) ($get('quantity') ?? 1);

                                            if ($productId) {
                                                $available = \App\Filament\Resources\TransactionResource::resolveAvailableProductSerials($get);
                                                return array_slice($available, 0, $quantity); // Take only needed quantity
                                            } elseif ($bundlingId) {
                                                $currentTransactionId = $get('../../id');
                                                $currentDetailTransactionId = $get('id');
                                                $startDate = $get('../../start_date') ? Carbon::parse($get('../../start_date')) : now();
                                                $endDate = $get('../../end_date') ? Carbon::parse($get('../../end_date')) : now();
                                                $result = \App\Filament\Resources\TransactionResource::resolveBundlingProductSerialsDisplay(
                                                    (int) $bundlingId,
                                                    $quantity,
                                                    $startDate,
                                                    $endDate,
                                                    $get('uuid'),
                                                    $get('../../detailTransactions') ?? [],
                                                    $currentTransactionId,
                                                    $currentDetailTransactionId
                                                );
                                                return $result['ids'] ?? [];
                                            }

                                            return [];
                                        })
                                        ->dehydrateStateUsing(function ($state) {
                                            // Ensure we return an array
                                            return is_array($state) ? $state : [];
                                        }),

                                ]),
                        ])
                        ->columns(1) // satu kolom per item repeater
                        ->grid(1)    // tampil dua item repeater per baris (di luar)
                        ->addActionLabel('Tambah Produk')
                        ->columnSpanFull()

                ]),
            Section::make('Keterangan')
                ->schema([

                    Grid::make('Pembayaran')
                        ->schema([
                            Select::make('promo_id')
                                ->label('Input kode Promo')
                                ->relationship('promo', 'name')
                                ->searchable()
                                ->nullable()
                                ->preload()
                                ->live()
                                ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                ->extraAttributes([
                                    'aria-label' => 'Select promotional discount code (optional)',
                                    'aria-describedby' => 'promo-help',
                                    'id' => 'promo_code_select'
                                ])
                                ->columnSpanFull(),

                            Placeholder::make('total_before_discount')
                                ->label('Total Sebelum Diskon')
                                ->content(function (Get $get) {
                                    // Ambil semua detail transaksi dari repeater
                                    $details = $get('detailTransactions');

                                    if (!$details || !is_array($details)) {
                                        return 'Rp 0';
                                    }

                                    $totalBeforeDiscount = 0;

                                    foreach ($details as $item) {
                                        $isBundling = (bool)($item['is_bundling'] ?? false);
                                        $customId = $isBundling ? ($item['bundling_id'] ?? '') : ($item['product_id'] ?? '');

                                        if (!$customId) continue;

                                        $price = 0;
                                        $quantity = $item['quantity'] ?? 1;

                                        if ($isBundling) {
                                            $bundling = \App\Models\Bundling::find($customId);
                                            if ($bundling) {
                                                $price = $bundling->price;
                                            }
                                        } else {
                                            $product = \App\Models\Product::find($customId);
                                            if ($product) {
                                                $price = $product->price;
                                            }
                                        }

                                        $totalBeforeDiscount += $price * $quantity;
                                    }

                                    return new HtmlString("Rp " . number_format($totalBeforeDiscount, 0, ',', '.'));
                                })
                                ->reactive()
                                ->columnSpan(1)
                                ->extraAttributes(['class' => 'text-lg font-bold']),
                            Placeholder::make('discount_given')
                                ->label('Diskon Diberikan')
                                ->content(function (Get $get) use (&$totalBeforeDiscount) {
                                    // Ambil promo_id
                                    $promoId = $get('promo_id');
                                    if (!$promoId) return 'Rp 0';

                                    // Hitung total sebelum diskon
                                    $details = $get('detailTransactions');
                                    $totalBeforeDiscount = 0;

                                    foreach ($details as $item) {
                                        $isBundling = (bool)($item['is_bundling'] ?? false);
                                        $customId = $isBundling ? ($item['bundling_id'] ?? '') : ($item['product_id'] ?? '');

                                        if (!$customId) continue;

                                        $price = 0;
                                        $quantity = $item['quantity'] ?? 1;

                                        if ($isBundling) {
                                            $bundling = \App\Models\Bundling::find($customId);
                                            if ($bundling) {
                                                $price = $bundling->price;
                                            }
                                        } else {
                                            $product = \App\Models\Product::find($customId);
                                            if ($product) {
                                                $price = $product->price;
                                            }
                                        }

                                        $totalBeforeDiscount += $price * $quantity;
                                    }

                                    // Ambil promo
                                    $promo = \App\Models\Promo::find($promoId);
                                    if (!$promo || !is_array($promo->rules)) return 'Rp 0';

                                    $rules = $promo->rules;
                                    $duration = $get('duration') ?? 1;
                                    $discountGiven = 0;

                                    if ($promo->type === 'day_based') {
                                        $groupSize = isset($rules[0]['group_size']) ? (int)$rules[0]['group_size'] : 1;
                                        $payDays = isset($rules[0]['pay_days']) ? (int)$rules[0]['pay_days'] : $groupSize;

                                        $discountedDays = (int)($duration / $groupSize) * $payDays;
                                        $remainingDays = $duration % $groupSize;
                                        $daysToPay = $discountedDays + $remainingDays;

                                        $discountGiven = max(0, $totalBeforeDiscount - ($totalBeforeDiscount / $duration * $daysToPay));
                                    } elseif ($promo->type === 'percentage') {
                                        $percentage = isset($rules[0]['percentage']) ? (float)$rules[0]['percentage'] : 0;
                                        $discountGiven = ($totalBeforeDiscount * ($percentage / 100));
                                    } elseif ($promo->type === 'nominal') {
                                        $nominal = isset($rules[0]['nominal']) ? (float)$rules[0]['nominal'] : 0;
                                        $discountGiven = min($nominal, $totalBeforeDiscount);
                                    }

                                    return "Rp " . number_format((int)$discountGiven, 0, ',', '.');
                                })
                                ->reactive()
                                ->extraAttributes(['class' => 'text-lg font-bold'])
                                ->columnSpan(1),


                            Placeholder::make('additional_services_total')
                                ->label('Total Additional Services')
                                ->content(function (Get $get) {
                                    // Calculate additional services fees from repeater
                                    $additionalFees = 0;
                                    $additionalServices = $get('additional_services') ?? [];
                                    if (is_array($additionalServices)) {
                                        foreach ($additionalServices as $service) {
                                            if (is_array($service) && isset($service['amount'])) {
                                                $additionalFees += (int)($service['amount'] ?? 0);
                                            }
                                        }
                                    }

                                    // Legacy support for old structure
                                    $additionalFees += (int)($get('additional_fee_1_amount') ?? 0);
                                    $additionalFees += (int)($get('additional_fee_2_amount') ?? 0);
                                    $additionalFees += (int)($get('additional_fee_3_amount') ?? 0);

                                    return "Rp " . number_format($additionalFees, 0, ',', '.');
                                })
                                ->reactive()
                                ->extraAttributes(['class' => 'text-lg font-medium text-blue-600'])
                                ->columnSpan(1),

                            Placeholder::make('grand_total_display')
                                ->label('Grand Total')
                                ->live(debounce: 500)
                                ->content(fn(Get $get) => "Rp " . number_format(static::calculateGrandTotal($get), 0, ',', '.'))
                                ->extraAttributes(['class' => 'text-lg font-bold'])
                                ->columnSpan(1),


                            Hidden::make('grand_total')
                                ->label('Grand Total')
                                ->reactive()
                                ->default(function (Get $get) {
                                    $grandTotal = static::calculateGrandTotal($get);
                                    return $grandTotal; // Simpan sebagai INT
                                }),

                            TextInput::make('down_payment')
                                ->label('Jumlah Pembayaran/DP')
                                ->numeric()
                                ->default(0)
                                ->live(debounce: 500)
                                ->extraAttributes([
                                    'aria-label' => 'Enter down payment amount in Rupiah',
                                    'aria-describedby' => 'down-payment-help',
                                    'id' => 'down_payment_input'
                                ])
                                ->rules([
                                    function (Get $get) {
                                        $grandTotal = static::calculateGrandTotal($get);
                                        if ($grandTotal <= 0) return ['required', 'min:0'];

                                        $min = max(0, floor($grandTotal * 0.5)); // 50% minimum including fees

                                        return [
                                            'required',
                                            'numeric',
                                            'min:0',
                                            function ($attribute, $value, $fail) use ($min, $grandTotal) {
                                                $value = (int)$value;
                                                if ($value < $min) {
                                                    $fail("Nilai harus minimal Rp " . number_format($min, 0, ',', '.') . " (50% dari total termasuk additional services)");
                                                }
                                                if ($value > $grandTotal) {
                                                    $fail("Nilai tidak boleh melebihi total Rp " . number_format($grandTotal, 0, ',', '.'));
                                                }
                                            },
                                        ];
                                    }
                                ])
                                ->default(function (Get $get) {
                                    $grandTotal = static::calculateGrandTotal($get);
                                    return max(0, floor($grandTotal * 0.5));
                                })
                                ->helperText(function (Get $get) {
                                    $grandTotal = static::calculateGrandTotal($get);
                                    $minPayment = max(0, floor($grandTotal * 0.5));
                                    return 'Pembayaran minimal 50% (termasuk additional services): Rp ' . number_format($minPayment, 0, ',', '.') .
                                        ' - Maksimal: Rp ' . number_format($grandTotal, 0, ',', '.');
                                })
                                ->minValue(function (Get $get) {
                                    $grandTotal = static::calculateGrandTotal($get);
                                    return max(0, floor($grandTotal * 0.5));
                                })
                                ->maxValue(fn(Get $get) => static::calculateGrandTotal($get))
                                ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                    $grandTotal = static::calculateGrandTotal($get);
                                    $downPayment = max(0, (int)($state ?? 0));

                                    // Validasi dan koreksi nilai
                                    if ($grandTotal <= 0) {
                                        $set('down_payment', 0);
                                        $set('remaining_payment', 0);
                                        $set('booking_status', 'cancel');
                                        return;
                                    }

                                    $minPayment = max(0, floor($grandTotal * 0.5));

                                    // Auto-correct invalid values
                                    if ($downPayment < $minPayment && $downPayment > 0) {
                                        $downPayment = $minPayment;
                                        $set('down_payment', $downPayment);
                                    } elseif ($downPayment > $grandTotal) {
                                        $downPayment = $grandTotal;
                                        $set('down_payment', $downPayment);
                                    }

                                    // Calculate remaining payment
                                    $remaining = max(0, $grandTotal - $downPayment);
                                    $set('remaining_payment', $remaining);
                                    $set('grand_total', $grandTotal);

                                    // Update booking status based on payment
                                    static::updateBookingStatusBasedOnPayment($set, $downPayment, $grandTotal);
                                })
                                ->columnSpanFull()
                                ->step(500),
                            Placeholder::make('remaining_payment')
                                ->label('Pelunasan')
                                ->content(function (Get $get) {
                                    $grandTotal = static::calculateGrandTotal($get);
                                    $downPayment = (int)($get('down_payment') ?? 0);
                                    $remainingPayment = max(0, $grandTotal - $downPayment);

                                    // Format sebagai Rupiah
                                    $formattedRemaining = 'Rp ' . number_format($remainingPayment, 0, ',', '.');

                                    // Kembalikan sebagai HTML terformat
                                    return new HtmlString($formattedRemaining);
                                })
                                ->columnSpanFull(),

                            Hidden::make('remaining_payment')
                                ->label('Pelunasan')
                                ->default(function (Get $get) {
                                    $grandTotal = static::calculateGrandTotal($get);
                                    $downPayment = (int)$get('down_payment');
                                    return max(0, $grandTotal - $downPayment); // Simpan sebagai INT
                                }),
                        ])
                        ->columnSpan(1)
                        ->columns([
                            'sm' => 1,
                            'md' => 3,
                        ]),
                    Grid::make('Status dan Note')
                        ->schema([
                            ToggleButtons::make('booking_status')
                                ->options([
                                    'booking' => 'booking',
                                    'paid' => 'paid',
                                    'cancel' => 'cancel',
                                    'on_rented' => 'on_rented',
                                    'done' => 'done',
                                ])
                                ->extraAttributes([
                                    'aria-label' => 'Select booking status',
                                    'aria-describedby' => 'booking-status-help',
                                    'role' => 'radiogroup'
                                ])
                                ->icons([
                                    'booking' => 'heroicon-o-clock',
                                    'cancel' => 'heroicon-o-x-circle',
                                    'on_rented' => 'heroicon-o-shopping-bag',
                                    'done' => 'heroicon-o-check',
                                    'paid' => 'heroicon-o-banknotes',
                                ])
                                ->colors([
                                    'booking' => 'warning',
                                    'cancel' => 'danger',
                                    'on_rented' => 'info',
                                    'done' => 'success',
                                    'paid' => 'success',
                                ])
                                ->inline()
                                ->columnSpanFull()
                                ->grouped()
                                ->reactive()
                                ->default('booking')
                                ->helperText(function (Get $get) {
                                    $status = $get('booking_status');
                                    switch ($status) {
                                        case 'booking':
                                            return new HtmlString('Masih <strong style="color:red">DP</strong> atau <strong style="color:red">belum pelunasan</strong>');
                                        case 'paid':
                                            return new HtmlString('<strong style="color:green">Sewa sudah lunas</strong> tapi <strong style="color:red">barang belum diambil</strong>.');
                                        case 'on_rented':
                                            return new HtmlString('Sewa sudah <strong style="color:blue">lunas</strong> dan barang sudah <strong style="color:blue">diambil</strong>');
                                        case 'cancel':
                                            return new HtmlString('<strong style="color:red">Sewa dibatalkan.</strong>');
                                        case 'done':
                                            return new HtmlString('<strong style="color:green">Sudah selesai disewa dan barang sudah diterima.</strong>');
                                    }
                                })
                                ->afterStateUpdated(function (Set $set, Get $get, string $state) use (&$isUpdating) {
                                    // Hindari loop reaktivitas
                                    if ($isUpdating) {
                                        return;
                                    }

                                    $isUpdating = true;

                                    $grandTotal = static::calculateGrandTotal($get);

                                    // Atur down_payment berdasarkan status dan handle cancellation fee
                                    if (in_array($state, ['paid', 'on_rented', 'done'])) {
                                        $set('down_payment', $grandTotal);
                                    } elseif ($state === 'cancel') {
                                        // Apply cancellation fee (50% of grand total)
                                        $cancellationFee = (int)floor($grandTotal * 0.5);
                                        $set('cancellation_fee', $cancellationFee);
                                        $set('down_payment', $cancellationFee);
                                    } else {
                                        $set('down_payment', max(0, (int)($grandTotal / 2)));
                                    }

                                    $isUpdating = false;
                                    $set('remaining_payment', max(0, $grandTotal - $get('down_payment')));
                                    $set('grand_total', $grandTotal);
                                }),
                            TextInput::make('note')
                                ->label('Catatan Sewa')
                                ->extraAttributes([
                                    'aria-label' => 'Enter additional rental notes or comments',
                                    'aria-describedby' => 'note-help',
                                    'id' => 'rental_note_input'
                                ]),

                            // Additional Services Fields
                            Section::make('Additional Services')
                                ->schema([
                                    Repeater::make('additional_services')
                                        ->schema([
                                            TextInput::make('name')
                                                ->label('Service Name')
                                                ->placeholder('e.g., Damage repair, Late return, Extra service')
                                                ->required()
                                                ->columnSpan(1),
                                            TextInput::make('amount')
                                                ->label('Service Amount')
                                                ->numeric()
                                                ->default(0)
                                                ->minValue(0)
                                                ->live()
                                                ->prefix('Rp')
                                                ->inputMode('decimal')
                                                ->step(1000)
                                                ->formatStateUsing(fn($state) => $state ? number_format($state, 0, '', '') : '')
                                                ->dehydrateStateUsing(fn($state) => (int) str_replace(',', '', $state))
                                                ->afterStateUpdated(fn($state, $set, $get) => $set('../../grand_total', static::calculateGrandTotal($get)))
                                                ->columnSpan(1),
                                        ])
                                        ->columns(2)
                                        ->addActionLabel('Add Additional Service')
                                        ->defaultItems(1)
                                        ->collapsible()
                                        ->columnSpanFull(),

                                    TextInput::make('cancellation_fee')
                                        ->label('Cancellation Fee')
                                        ->numeric()
                                        ->default(0)
                                        ->minValue(0)
                                        ->readonly()
                                        ->helperText('Automatically calculated when status is set to cancel')
                                        ->columnSpanFull(),
                                ])
                                ->collapsible()
                                ->columnSpanFull(),



                        ])
                        ->columnSpan(1)
                        ->columns([
                            'sm' => 1,
                            'md' => 2,
                        ]),
                ])->columns(2),
        ]);
    }
    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                InfoSection::make('Transaction Information')
                    ->schema([
                        InfoGrid::make(2)
                            ->schema([
                                TextEntry::make('booking_transaction_id')
                                    ->label('Transaction ID'),
                                TextEntry::make('customer.name')
                                    ->label('Customer'),
                                TextEntry::make('start_date')
                                    ->label('Start Date')
                                    ->dateTime('d M Y, H:i'),
                                TextEntry::make('end_date')
                                    ->label('End Date')
                                    ->dateTime('d M Y, H:i'),
                                TextEntry::make('duration')
                                    ->label('Duration')
                                    ->suffix(' days'),
                                TextEntry::make('booking_status')
                                    ->label('Status')
                                    ->badge()
                                    ->color(fn(string $state): string => match ($state) {
                                        'booking' => 'warning',
                                        'cancel' => 'danger',
                                        'on_rented' => 'info',
                                        'done' => 'success',
                                        'paid' => 'success',
                                    }),
                            ])
                    ]),

                InfoSection::make('Products')
                    ->schema([
                        TextEntry::make('product_info')
                            ->label('Products/Bundles')
                            ->getStateUsing(function ($record) {
                                $products = [];

                                // Use already eager-loaded relationships to avoid N+1 queries
                                if ($record->relationLoaded('detailTransactions')) {
                                    foreach ($record->detailTransactions as $detail) {
                                        if ($detail->bundling_id && $detail->relationLoaded('bundling') && $detail->bundling) {
                                            $products[] = $detail->bundling->name . ' (Bundle)';
                                        } elseif ($detail->product_id && $detail->relationLoaded('product') && $detail->product) {
                                            $products[] = $detail->product->name;
                                        }
                                    }
                                }

                                return implode(', ', array_unique($products)) ?: 'N/A';
                            }),
                    ]),

                InfoSection::make('Financial Information')
                    ->schema([
                        InfoGrid::make(4)
                            ->schema([
                                TextEntry::make('grand_total')
                                    ->label('Grand Total')
                                    ->formatStateUsing(
                                        fn(string $state): string =>
                                        'Rp ' . number_format((int) $state, 0, ',', '.')
                                    ),
                                TextEntry::make('down_payment')
                                    ->label('Down Payment')
                                    ->formatStateUsing(
                                        fn(string $state): string =>
                                        'Rp ' . number_format((int) $state, 0, ',', '.')
                                    ),
                                TextEntry::make('remaining_payment')
                                    ->label('Remaining Payment')
                                    ->formatStateUsing(
                                        fn(string $state): string =>
                                        $state == '0' ? 'LUNAS' : 'Rp ' . number_format((int) $state, 0, ',', '.')
                                    ),
                                TextEntry::make('cancellation_fee')
                                    ->label('Cancellation Fee')
                                    ->formatStateUsing(
                                        fn(?string $state, $record): string =>
                                        $record->booking_status === 'cancel' && $state && $state != '0' ? 'Rp ' . number_format((int) $state, 0, ',', '.') : '-'
                                    )
                                    ->color(fn($record): string => $record->booking_status === 'cancel' ? 'danger' : 'gray')
                                    ->visible(fn($record): bool => $record->booking_status === 'cancel'),
                            ])
                    ]),

                InfoSection::make('Additional Information')
                    ->schema([
                        TextEntry::make('promo.name')
                            ->label('Promo Applied')
                            ->default('None'),
                        TextEntry::make('note')
                            ->label('Notes')
                            ->default('No notes'),
                    ])
            ]);
    }



    public static function table(Table $table): Table
    {
        return $table
            ->defaultPaginationPageOption(25) // Reduced for better performance
            ->deferLoading() // Enable deferred loading for better UX
            ->poll('60s') // Refresh every minute
            ->modifyQueryUsing(function (Builder $query) {
                // Apply optimized select and eager loading
                $query->select(static::getSelectColumns())
                    ->with(static::getEagerLoadRelations());

                // Check for table search parameter
                $searchTerm = request('tableSearch');

                if ($searchTerm && strlen($searchTerm) >= 2) {
                    // Use repository for optimized search
                    return static::getRepository()->searchTransactions($searchTerm, 25)->getCollection()->toQuery();
                }

                // Apply default ordering for better performance
                $query->orderBy('transactions.created_at', 'desc');

                return $query;
            })
            ->searchable()
            ->searchOnBlur()
            ->searchDebounce('500ms')
            ->columns([
                TextColumn::make('booking_transaction_id')
                    ->label('ID')
                    ->searchable()
                    ->size(TextColumnSize::ExtraSmall)

                    ->sortable(),
                TextColumn::make('customer.name')
                    ->label('Nama')
                    ->size(TextColumnSize::ExtraSmall)

                    ->searchable(),
                TextColumn::make('customer.phone_number')
                    ->label('WA')
                    ->formatStateUsing(function ($state, $record) {
                        // Get phone number from customer relationship
                        $phoneNumber = $record->customer->customerPhoneNumbers->first()->phone_number ?? $record->customer->phone_number ?? $state;

                        // Clean and format phone number
                        $cleanNumber = preg_replace('/[^0-9]/', '', $phoneNumber);

                        // Handle different formats
                        if (str_starts_with($cleanNumber, '62')) {
                            // Already has country code
                            $formattedNumber = '+' . $cleanNumber;
                        } elseif (str_starts_with($cleanNumber, '0')) {
                            // Starts with 0, replace with +62
                            $formattedNumber = '+62' . substr($cleanNumber, 1);
                        } else {
                            // No country code or leading 0, add +62
                            $formattedNumber = '+62' . $cleanNumber;
                        }

                        return $formattedNumber;
                    })
                    ->url(function ($record) {
                        // Get phone number from customer relationship
                        $phoneNumber = $record->customer->customerPhoneNumbers->first()->phone_number ?? $record->customer->phone_number ?? '';

                        // Clean phone number (remove non-digits)
                        $cleanNumber = preg_replace('/[^0-9]/', '', $phoneNumber);

                        // Handle different formats for WhatsApp URL
                        if (str_starts_with($cleanNumber, '62')) {
                            // Already has country code 62
                            $waNumber = $cleanNumber;
                        } elseif (str_starts_with($cleanNumber, '0')) {
                            // Starts with 0, replace with 62
                            $waNumber = '62' . substr($cleanNumber, 1);
                        } else {
                            // No country code, add 62
                            $waNumber = '62' . $cleanNumber;
                        }

                        return 'https://wa.me/' . $waNumber;
                    })
                    ->openUrlInNewTab()
                    ->color('success')
                    ->icon('heroicon-m-chat-bubble-left-ellipsis')
                    ->size(TextColumnSize::ExtraSmall)
                    ->tooltip('Click to open WhatsApp chat'),
                TextColumn::make('product_info')
                    ->label('Product + Serial Numbers')
                    ->size(TextColumnSize::ExtraSmall)
                    ->html()
                    ->wrap()
                    ->getStateUsing(function ($record) {
                        $products = [];
                        $searchTerm = request('tableSearch');

                        // Use already eager-loaded relationships to avoid N+1 queries
                        if ($record->relationLoaded('detailTransactions')) {
                            foreach ($record->detailTransactions as $detail) {
                                $productInfo = '';
                                $quantity = $detail->quantity ?? 1;

                                if ($detail->bundling_id && $detail->relationLoaded('bundling') && $detail->bundling) {
                                    // BUNDLING: Show bundling name + products inside + serial numbers
                                    $bundleName = static::highlightSearchTerm($detail->bundling->name, $searchTerm);
                                    $productInfo = "<strong>{$bundleName} (Bundle)";
                                    if ($quantity > 1) {
                                        $productInfo .= " x{$quantity}";
                                    }
                                    $productInfo .= ":</strong><br>";

                                    // Get bundling products and their serial numbers
                                    if ($detail->bundling->relationLoaded('products')) {
                                        $bundlingDetails = [];
                                        foreach ($detail->bundling->products as $bundlingProduct) {
                                            $requiredQty = $quantity * ($bundlingProduct->pivot->quantity ?? 1);

                                            // Get serial numbers for this product in this detail transaction
                                            $serialNumbers = [];
                                            if ($detail->relationLoaded('productItems')) {
                                                foreach ($detail->productItems as $item) {
                                                    if ($item->product_id == $bundlingProduct->id) {
                                                        $serialNumbers[] = static::highlightSearchTerm($item->serial_number, $searchTerm);
                                                    }
                                                }
                                            }

                                            $productDetail = "&nbsp;&nbsp;• {$bundlingProduct->name}";
                                            if ($requiredQty > 1) {
                                                $productDetail .= " x{$requiredQty}";
                                            }

                                            if (!empty($serialNumbers)) {
                                                $productDetail .= ": " . implode(', ', $serialNumbers);
                                            } else {
                                                $productDetail .= ": <em>No serial numbers</em>";
                                            }

                                            $bundlingDetails[] = $productDetail;
                                        }
                                        $productInfo .= implode('<br>', $bundlingDetails);
                                    }
                                } elseif ($detail->product_id && $detail->relationLoaded('product') && $detail->product) {
                                    // SINGLE PRODUCT: Show product name + serial numbers
                                    $productName = static::highlightSearchTerm($detail->product->name, $searchTerm);
                                    $productInfo = "<strong>{$productName}";
                                    if ($quantity > 1) {
                                        $productInfo .= " x{$quantity}";
                                    }
                                    $productInfo .= ":</strong> ";

                                    // Get serial numbers for this product
                                    $serialNumbers = [];
                                    if ($detail->relationLoaded('productItems')) {
                                        foreach ($detail->productItems as $item) {
                                            $serialNumbers[] = static::highlightSearchTerm($item->serial_number, $searchTerm);
                                        }
                                    }

                                    if (!empty($serialNumbers)) {
                                        $productInfo .= implode(', ', $serialNumbers);
                                    } else {
                                        $productInfo .= "<em>No serial numbers</em>";
                                    }
                                }

                                if ($productInfo) {
                                    $products[] = $productInfo;
                                }
                            }
                        }

                        return !empty($products) ? implode('<br><br>', $products) : 'N/A';
                    })
                    ->html()
                    ->searchable()
                    ->wrap()
                    ->lineClamp(null), // Remove line clamp to show all content
                // TextColumn::make('detailTransactions.productItems.serial_number')
                //     ->label('S/N')
                //     ->size(TextColumnSize::ExtraSmall)
                //     ->formatStateUsing(function ($record) {
                //         $serialNumbers = [];
                //         $searchTerm = request('tableSearch');

                //         foreach ($record->detailTransactions as $detail) {
                //             foreach ($detail->productItems as $item) {
                //                 $serialNumbers[] = static::highlightSearchTerm($item->serial_number, $searchTerm);
                //             }
                //         }

                //         if (empty($serialNumbers)) {
                //             return '-';
                //         }

                //         if (count($serialNumbers) <= 5) {
                //             return implode(', ', $serialNumbers);
                //         }

                //         $first5 = array_slice($serialNumbers, 0, 5);
                //         $remaining = count($serialNumbers) - 5;

                //         return implode(', ', $first5) . " <span style='color: #6b7280; font-style: italic;'>dan {$remaining} lainnya</span>";
                //     })
                //     ->html()
                //     ->searchable()
                //     ->wrap(),
                TextColumn::make('start_date')
                    ->label('Start')
                    ->wrap()
                    ->size(TextColumnSize::ExtraSmall)
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('end_date')
                    ->label('End')
                    ->wrap()
                    ->size(TextColumnSize::ExtraSmall)
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('grand_total')
                    ->label('Tot')
                    ->formatStateUsing(fn(string $state): HtmlString => new HtmlString(
                        'Rp ' . number_format((int) $state, 0, ',', '.')
                    ))
                    ->size(TextColumnSize::ExtraSmall),
                TextInputColumn::make('down_payment')
                    ->label('DP')
                    ->type('number')
                    ->default(fn(Transaction $record): int => (int)($record->grand_total / 2))
                    ->rules([
                        'required',
                        'numeric',
                        'min:0',
                    ])
                    ->afterStateUpdated(function ($record, $state) {
                        if ($record && $state !== null) {
                            $grandTotal = (int)$record->grand_total;
                            $downPayment = (int)$state;

                            // Calculate remaining payment
                            $remainingPayment = max(0, $grandTotal - $downPayment);

                            // Update the record
                            $record->update([
                                'down_payment' => $downPayment,
                                'remaining_payment' => $remainingPayment,
                            ]);

                            // Update booking status based on payment
                            if ($grandTotal <= 0) {
                                $record->update(['booking_status' => 'cancel']);
                            } elseif ($downPayment <= 0) {
                                $record->update(['booking_status' => 'cancel']);
                            } elseif ($downPayment >= $grandTotal) {
                                // Full payment - set to paid if not already on_rented/done
                                if (!in_array($record->booking_status, ['on_rented', 'done'])) {
                                    $record->update(['booking_status' => 'paid']);
                                }
                            } else {
                                // Partial payment
                                $minPayment = max(0, floor($grandTotal * 0.5));
                                if ($downPayment >= $minPayment) {
                                    $record->update(['booking_status' => 'booking']);
                                } else {
                                    $record->update(['booking_status' => 'cancel']);
                                }
                            }
                        }
                    })
                    ->sortable(),

                TextColumn::make('remaining_payment')
                    ->label('Sisa')
                    ->size(TextColumnSize::ExtraSmall)
                    ->formatStateUsing(fn(string $state): HtmlString => new HtmlString(
                        $state == '0' ? '<strong style="color: green">LUNAS</strong>' : 'Rp ' . number_format((int) $state, 0, ',', '.')
                    ))
                    ->sortable(),

                TextColumn::make('cancellation_fee')
                    ->label('Cancel Fee')
                    ->size(TextColumnSize::ExtraSmall)
                    ->formatStateUsing(fn(?string $state): HtmlString => new HtmlString(
                        $state && $state != '0' ? 'Rp ' . number_format((int) $state, 0, ',', '.') : '-'
                    ))
                    ->visible(fn($record) => $record && $record->booking_status === 'cancel')
                    ->color('danger'),
                TextColumn::make('booking_status')
                    ->label('')
                    ->wrap()
                    ->size(TextColumnSize::ExtraSmall)
                    ->icon(fn(string $state): string => match ($state) {
                        'booking' => 'heroicon-o-clock',
                        'cancel' => 'heroicon-o-x-circle',
                        'on_rented' => 'heroicon-o-shopping-bag',
                        'done' => 'heroicon-o-check',
                        'paid' => 'heroicon-o-banknotes',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'booking' => 'warning',
                        'cancel' => 'danger',
                        'on_rented' => 'info',
                        'done' => 'success',
                        'paid' => 'success',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('customer_id')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('booking_status')
                    ->multiple()
                    ->options([
                        'booking' => 'Booking',
                        'cancel' => 'Cancel',
                        'on_rented' => 'On Rented',
                        'done' => 'Done',
                        'paid' => 'Paid',
                    ])
                    ->default(['booking', 'paid', 'on_rented']),
            ])
            ->headerActions([
                Action::make('downloadTemplate')
                    ->label('Download Reference Template')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('warning')
                    ->tooltip('Note: Transaction import is not supported due to business complexity')
                    ->action(function () {
                        $service = new TransactionImportExportService();
                        $filePath = $service->generateTemplate();
                        return response()->download($filePath, 'transaction_reference_template.xlsx')->deleteFileAfterSend();
                    }),

                Action::make('export')
                    ->label('Export All')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->action(function () {
                        $service = new TransactionImportExportService();
                        $filePath = $service->exportTransactions();
                        return response()->download($filePath, 'transactions_export_' . date('Y-m-d_H-i-s') . '.xlsx')->deleteFileAfterSend();
                    }),
            ])
            ->actions([
                BulkActionGroup::make([
                    Action::make('booking')
                        ->icon('heroicon-o-clock')
                        ->color('warning')
                        ->label('Booking')
                        ->requiresConfirmation()
                        ->modalHeading('Ubah Status -> BOOKING')
                        ->modalDescription(fn(): HtmlString => new HtmlString('Apakah Anda yakin ingin mengubah status booking menjadi Booking? <br> <strong style="color:red">Harap sesuaikan kolom DP, Jika sudah lunas maka action akan gagal</strong>'))
                        ->modalSubmitActionLabel('Ya, Ubah Status')
                        ->modalCancelActionLabel('Batal')
                        ->action(function (Transaction $record) {
                            $downPayment = (int) ($record->down_payment ?? 0);
                            $grandTotal = (int) ($record->grand_total ?? 0);

                            if ($downPayment === $grandTotal) {
                                Notification::make()
                                    ->danger()
                                    ->title('UBAH STATUS GAGAL')
                                    ->body('Sesuaikan DP, jika sudah lunas maka statusnya adalah "Paid", "on_rented", atau "Done"')
                                    ->send();
                                return;
                            }

                            try {
                                $record->update(['booking_status' => 'booking']);
                                Notification::make()
                                    ->success()
                                    ->title('Berhasil Mengubah Status Booking Transaksi')
                                    ->send();
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->danger()
                                    ->title('Gagal Update Status')
                                    ->body('Terjadi kesalahan saat memperbarui status transaksi.')
                                    ->send();
                            }
                        }),
                    Action::make('paid')
                        ->icon('heroicon-o-banknotes') // Ikon untuk action
                        ->color('success') // Warna action (success biasanya hijau)
                        ->label('Paid') // Label yang ditampilkan
                        ->requiresConfirmation() // Memastikan action memerlukan konfirmasi sebelum dijalankan
                        ->action(function (Transaction $record) {
                            // Update booking_status menjadi 'paid'
                            $record->update([
                                'booking_status' => 'paid',
                                'down_payment' => $record->grand_total, // Set down_payment sama dengan grand_total
                            ]);

                            // Notifikasi sukses
                            Notification::make()
                                ->success()
                                ->title('Berhasil Mengubah Status Transaksi')
                                ->body('Status transaksi berhasil diubah menjadi "Paid" dan down payment disesuaikan dengan grand total.')
                                ->send();
                        }),
                    Action::make('cancel')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->label('cancel')
                        ->requiresConfirmation()
                        ->action(function (Transaction $record) {
                            // Apply 50% cancellation fee
                            $cancellationFee = (int)floor($record->grand_total * 0.5);
                            $record->update([
                                'booking_status' => 'cancel',
                                'cancellation_fee' => $cancellationFee,
                                'down_payment' => $cancellationFee,
                            ]);

                            Notification::make()
                                ->success()
                                ->title('Berhasil Mengubah Status Booking Transaksi')
                                ->body('Biaya pembatalan 50% telah diterapkan.')
                                ->send();
                        }),

                    Action::make('on_rented')
                        ->icon('heroicon-o-shopping-bag')
                        ->color('info')
                        ->label('on_rented')
                        ->requiresConfirmation()
                        ->action(function (Transaction $record) {
                            $record->update([
                                'booking_status' => 'on_rented',
                                'down_payment' => $record->grand_total,
                            ]);

                            Notification::make()
                                ->success()
                                ->title('Berhasil Mengubah Status Booking Transaksi')
                                ->send();
                        }),

                    Action::make('done')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->label('done')
                        ->requiresConfirmation()
                        ->action(function (Transaction $record) {
                            $record->update(['booking_status' => 'done']);

                            Notification::make()
                                ->success()
                                ->title('Berhasil Mengubah Status Booking Transaksi')
                                ->send();
                        })
                ])
                    ->label('status')
                    ->size(ActionSize::ExtraSmall),
                Action::make('Invoice')
                    ->color('success')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->label('invoice')
                    ->url(fn(Transaction $record) => route('pdf', $record))
                    ->openUrlInNewTab()
                    ->size(ActionSize::ExtraSmall),

                BulkActionGroup::make([
                    ViewAction::make()
                        ->icon('heroicon-o-eye')
                        ->label('view')
                        ->size(ActionSize::ExtraSmall),

                    EditAction::make()
                        ->icon('heroicon-o-pencil')
                        ->label('edit')

                        ->size(ActionSize::ExtraSmall),

                    DeleteAction::make()
                        ->icon('heroicon-o-trash')
                        ->label('delete')

                        ->size(ActionSize::ExtraSmall),


                    ActivityLogTimelineTableAction::make('Activities')
                        ->label('log')
                        ->timelineIcons([
                            'created' => 'heroicon-m-check-badge',
                            'updated' => 'heroicon-m-pencil-square',
                        ])
                        ->timelineIconColors([
                            'created' => 'info',
                            'updated' => 'warning',
                        ])
                        ->icon('heroicon-m-clock'),








                ])
                    ->label('edit')
                    ->size(ActionSize::ExtraSmall),



            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('hapus'),
                    BulkAction::make('booking')
                        ->icon('heroicon-o-clock')
                        ->color('warning')
                        ->label('Booking')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records) {
                            $records->each(function ($record) {
                                $record->update(['booking_status' => 'booking']);
                            });
                            Notification::make()
                                ->success()
                                ->title('Berhasil Mengubah Status Booking Transaksi')
                                ->send();
                        }),


                    BulkAction::make('paid')
                        ->icon('heroicon-o-banknotes')
                        ->color('success')
                        ->label('paid')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()


                        ->action(function (Collection $records) {
                            $records->each(function ($record) {
                                $record->update(['booking_status' => 'paid']);
                            });
                            Notification::make()
                                ->success()
                                ->title('Berhasil Mengubah Status Booking Transaksi')
                                ->send();
                        }),

                    BulkAction::make('cancel')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->label('cancel')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records) {
                            $records->each(function ($record) {
                                $cancellationFee = (int)floor($record->grand_total * 0.5);
                                $record->update([
                                    'booking_status' => 'cancel',
                                    'cancellation_fee' => $cancellationFee,
                                    'down_payment' => $cancellationFee,
                                ]);
                            });
                            Notification::make()
                                ->success()
                                ->title('Berhasil Mengubah Status Booking Transaksi')
                                ->body('Biaya pembatalan 50% telah diterapkan pada semua transaksi.')
                                ->send();
                        }),

                    BulkAction::make('on_rented')
                        ->icon('heroicon-o-shopping-bag')
                        ->color('info')
                        ->label('on_rented')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records) {
                            $records->each(function ($record) {
                                $record->update([
                                    'booking_status' => 'on_rented',
                                    'down_payment' => $record->grand_total,
                                ]);
                            });
                            Notification::make()
                                ->success()
                                ->title('Berhasil Mengubah Status Booking Transaksi')
                                ->send();
                        }),

                    BulkAction::make('done')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->label('done')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records) {
                            $records->each(function ($record) {
                                $record->update(['booking_status' => 'done']);
                            });
                            Notification::make()
                                ->success()
                                ->title('Berhasil Mengubah Status Booking Transaksi')
                                ->send();
                        }),

                    BulkAction::make('exportSelected')
                        ->label('Export Selected')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('info')
                        ->action(function (Collection $records) {
                            $service = new TransactionImportExportService();
                            $transactionIds = $records->pluck('id')->toArray();
                            $filePath = $service->exportTransactions($transactionIds);
                            return response()->download($filePath, 'transactions_selected_export_' . date('Y-m-d_H-i-s') . '.xlsx')->deleteFileAfterSend();
                        }),

                ]),
            ]);
    }
    protected function beforeSave(): void
    {
        // Validate serial assignments sebelum save
        $detailTransactions = $this->data['detailTransactions'] ?? [];

        foreach ($detailTransactions as $detail) {
            if (!empty($detail['product_id']) && empty($detail['productItems'])) {
                // Auto-assign jika kosong
                $availableItems = \App\Models\ProductItem::where('product_id', $detail['product_id'])
                    ->where('is_available', true)
                    ->limit($detail['quantity'] ?? 1)
                    ->pluck('id')
                    ->toArray();

                if (count($availableItems) >= ($detail['quantity'] ?? 1)) {
                    $this->data['detailTransactions'][array_search($detail, $detailTransactions)]['productItems'] = $availableItems;
                }
            }
        }
    }


    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransactions::route('/'),
            'create' => Pages\CreateTransaction::route('/create'),
            'view' => Pages\ViewTransaction::route('/{record}'),

            'edit' => Pages\EditTransaction::route('/{record}/edit'),

        ];
    }
}
