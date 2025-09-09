<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BaseOptimizedResource;
use App\Filament\Resources\TransactionResource\Number;
use App\Filament\Resources\TransactionResource\Pages;
use App\Services\ResourceCacheService;
use App\Repositories\TransactionRepository;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Services\PromoCalculationService;
use Filament\Forms\Components\DatePicker;

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
use Filament\Forms\Set;
use Filament\Forms\Get;

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
use Filament\Tables\Actions\ExportAction;
use Filament\Tables\Actions\ExportBulkAction;
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
            'transactions.*'
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
            'detailTransactions.bundling.bundlingProducts:id,bundling_id,product_id,quantity',
            'detailTransactions.bundling.bundlingProducts.product:id,name,price',
            'detailTransactions.productItems:id,serial_number,product_id',
            'promo:id,name,type,rules'
        ];
    }

    /**
     * Global search attributes for TransactionResource
     * Searches: booking_transaction_id, customer.name, product names, bundling names
     */
    public static function getGloballySearchableAttributes(): array
    {
        return [
            'booking_transaction_id',
            'customer.name',
        ];
    }

    /**
     * Override global search to include product and bundling names
     */
    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with([
            'customer:id,name',
            'detailTransactions:id,transaction_id,product_id,bundling_id',
            'detailTransactions.product:id,name',
            'detailTransactions.bundling:id,name',
            'detailTransactions.productItems:id,serial_number,product_id',
        ]);
    }

    /**
     * Override global search query to include product and bundling names with case-insensitive search
     */
    public static function getGlobalSearchQuery(string $search): Builder
    {
        $lowerSearch = strtolower($search);

        return static::getModel()::query()
            ->with([
                'customer:id,name',
                'detailTransactions:id,transaction_id,product_id,bundling_id',
                'detailTransactions.product:id,name',
                'detailTransactions.bundling:id,name',
            ])
            ->where(function (Builder $query) use ($lowerSearch) {
                $query->whereRaw('LOWER(booking_transaction_id) LIKE ?', ["%{$lowerSearch}%"])
                    ->orWhereHas('customer', function (Builder $q) use ($lowerSearch) {
                        $q->whereRaw('LOWER(name) LIKE ?', ["%{$lowerSearch}%"]);
                    })
                    ->orWhereHas('detailTransactions.product', function (Builder $q) use ($lowerSearch) {
                        $q->whereRaw('LOWER(name) LIKE ?', ["%{$lowerSearch}%"]);
                    })
                    ->orWhereHas('detailTransactions.bundling', function (Builder $q) use ($lowerSearch) {
                        $q->whereRaw('LOWER(name) LIKE ?', ["%{$lowerSearch}%"]);
                    });
            });
    }

    /**
     * Custom global search results details
     */
    public static function getGlobalSearchResultDetails($record): array
    {
        $details = [];

        // Add customer name
        if ($record->customer) {
            $details['Customer'] = $record->customer->name;
        }

        // Add product/bundling info
        $products = [];
        foreach ($record->detailTransactions as $detail) {
            if ($detail->bundling) {
                $products[] = $detail->bundling->name . ' (Bundle)';
            } elseif ($detail->product) {
                $products[] = $detail->product->name;
            }
        }

        if (!empty($products)) {
            $details['Products'] = implode(', ', array_unique($products));
        }

        return $details;
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
    protected static function resolveAvailableProductSerials(Get $get, ?\Filament\Forms\Set $set = null): array
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
        $currentTransactionId = $get('../../id'); // Get current transaction ID when editing

        if (!$productId || !$currentUuid) {
            return [];
        }

        // Batasi jumlah detail transaksi yang diproses untuk menghemat memori
        $allDetailTransactions = $get('../../detailTransactions') ?? [];
        if (is_array($allDetailTransactions) && count($allDetailTransactions) > 20) {
            // Jika terlalu banyak, ambil hanya 20 item terakhir untuk diproses
            $allDetailTransactions = array_slice($allDetailTransactions, -20);
        }

        // Ambil product_item_id dari tabel pivot yang sudah digunakan dalam repeater saat ini
        // Gunakan pendekatan yang lebih hemat memori
        $usedInCurrentRepeater = [];
        foreach ($allDetailTransactions as $row) {
            if (is_array($row) && isset($row['uuid']) && $row['uuid'] !== $currentUuid && isset($row['productItems'])) {
                if (is_array($row['productItems'])) {
                    foreach ($row['productItems'] as $itemId) {
                        $usedInCurrentRepeater[] = $itemId;
                    }
                }
            }
        }
        $usedInCurrentRepeater = array_unique($usedInCurrentRepeater);

        // Ambil product_item_id dari transaksi lain yang aktif (EXCLUDE current transaction when editing)
        // Gunakan query yang lebih efisien dengan select dan limit
        $usedInOtherTransactionsQuery = DB::table('detail_transaction_product_item')
            ->join('detail_transactions', 'detail_transaction_product_item.detail_transaction_id', '=', 'detail_transactions.id')
            ->join('transactions', 'detail_transactions.transaction_id', '=', 'transactions.id')
            ->whereIn('transactions.booking_status', ['booking', 'paid', 'on_rented'])
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('transactions.start_date', [$startDate, $endDate])
                    ->orWhereBetween('transactions.end_date', [$startDate, $endDate])
                    ->orWhere(function ($q2) use ($startDate, $endDate) {
                        $q2->where('transactions.start_date', '<=', $startDate)
                            ->where('transactions.end_date', '>=', $endDate);
                    });
            });

        // EXCLUDE current transaction when editing
        if ($currentTransactionId) {
            $usedInOtherTransactionsQuery->where('transactions.id', '!=', $currentTransactionId);
        }

        // Batasi jumlah hasil untuk menghemat memori
        $usedInOtherTransactions = $usedInOtherTransactionsQuery
            ->select('detail_transaction_product_item.product_item_id')
            ->limit(1000) // Batasi jumlah maksimum item yang diambil
            ->pluck('product_item_id')
            ->toArray();

        // Gabungkan semua ID yang harus dikeluarkan dengan cara yang lebih efisien
        $excludedIds = array_unique(array_merge($usedInCurrentRepeater, $usedInOtherTransactions));

        // Get currently assigned items to this detail transaction (for editing)
        $currentlyAssignedIds = [];
        $currentDetailTransactionId = $get('id');
        if ($currentDetailTransactionId) {
            $currentlyAssignedIds = DB::table('detail_transaction_product_item')
                ->join('product_items', 'detail_transaction_product_item.product_item_id', '=', 'product_items.id')
                ->where('detail_transaction_product_item.detail_transaction_id', $currentDetailTransactionId)
                ->where('product_items.product_id', $productId)
                ->select('detail_transaction_product_item.product_item_id')
                ->pluck('product_item_id')
                ->toArray();
        }

        // Ambil serial number yang tersedia dengan query yang lebih efisien
        // Gunakan raw query untuk performa lebih baik jika array excludedIds terlalu besar
        if (count($excludedIds) > 1000) {
            // Jika terlalu banyak ID yang diexclude, gunakan pendekatan alternatif
            // yang lebih hemat memori dengan hanya mengambil item yang tersedia
            $availableIds = DB::table('product_items')
                ->where('product_id', $productId)
                ->whereNotIn('id', function ($query) use ($startDate, $endDate, $currentTransactionId) {
                    $query->select('product_item_id')
                        ->from('detail_transaction_product_item')
                        ->join('detail_transactions', 'detail_transaction_product_item.detail_transaction_id', '=', 'detail_transactions.id')
                        ->join('transactions', 'detail_transactions.transaction_id', '=', 'transactions.id')
                        ->whereIn('transactions.booking_status', ['booking', 'paid', 'on_rented'])
                        ->where(function ($q) use ($startDate, $endDate) {
                            $q->whereBetween('transactions.start_date', [$startDate, $endDate])
                                ->orWhereBetween('transactions.end_date', [$startDate, $endDate])
                                ->orWhere(function ($q2) use ($startDate, $endDate) {
                                    $q2->where('transactions.start_date', '<=', $startDate)
                                        ->where('transactions.end_date', '>=', $endDate);
                                });
                        })
                        ->when($currentTransactionId, function ($q) use ($currentTransactionId) {
                            $q->where('transactions.id', '!=', $currentTransactionId);
                        });
                })
                ->limit($quantity + count($currentlyAssignedIds))
                ->pluck('id')
                ->toArray();

            // Tambahkan item yang saat ini sudah di-assign
            if (!empty($currentlyAssignedIds)) {
                $availableIds = array_unique(array_merge($availableIds, $currentlyAssignedIds));
            }
        } else {
            // Gunakan pendekatan normal jika jumlah ID yang diexclude masih dalam batas wajar
            $query = DB::table('product_items')
                ->where('product_id', $productId);

            if (!empty($excludedIds)) {
                $query->where(function ($q) use ($excludedIds, $currentlyAssignedIds) {
                    $q->whereNotIn('id', $excludedIds);
                    if (!empty($currentlyAssignedIds)) {
                        $q->orWhereIn('id', $currentlyAssignedIds);
                    }
                });
            }

            $availableIds = $query->limit($quantity + count($currentlyAssignedIds))
                ->pluck('id')
                ->toArray();
        }

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

        $bundling = Bundling::with('bundlingProducts.product')->find($bundlingId);

        if (!$bundling) {
            return ['ids' => [], 'display' => '-'];
        }

        // Generate UUID if missing
        if (!$currentUuid) {
            $currentUuid = (string) \Illuminate\Support\Str::uuid();
        }

        // Batasi jumlah detail transaksi yang diproses untuk menghemat memori
        if (count($allDetailTransactions) > 20) {
            // Jika terlalu banyak, ambil hanya 20 item terakhir untuk diproses
            $allDetailTransactions = array_slice($allDetailTransactions, -20);
        }

        // Ambil product_item_id dari tabel pivot yang sudah digunakan dalam repeater saat ini
        // Gunakan pendekatan yang lebih hemat memori
        $usedInCurrentRepeater = [];
        foreach ($allDetailTransactions as $row) {
            if (is_array($row) && isset($row['uuid']) && $row['uuid'] !== $currentUuid && isset($row['productItems'])) {
                if (is_array($row['productItems'])) {
                    foreach ($row['productItems'] as $itemId) {
                        $usedInCurrentRepeater[] = $itemId;
                    }
                }
            }
        }
        $usedInCurrentRepeater = array_unique($usedInCurrentRepeater);

        // Ambil product_item_id dari transaksi lain yang aktif (EXCLUDE current transaction when editing)
        // Gunakan query yang lebih efisien dengan select dan limit
        $usedInOtherTransactionsQuery = DB::table('detail_transaction_product_item')
            ->join('detail_transactions', 'detail_transaction_product_item.detail_transaction_id', '=', 'detail_transactions.id')
            ->join('transactions', 'detail_transactions.transaction_id', '=', 'transactions.id')
            ->whereIn('transactions.booking_status', ['booking', 'paid', 'on_rented'])
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('transactions.start_date', [$startDate, $endDate])
                    ->orWhereBetween('transactions.end_date', [$startDate, $endDate])
                    ->orWhere(function ($q2) use ($startDate, $endDate) {
                        $q2->where('transactions.start_date', '<=', $startDate)
                            ->where('transactions.end_date', '>=', $endDate);
                    });
            });

        // EXCLUDE current transaction when editing
        if ($currentTransactionId) {
            $usedInOtherTransactionsQuery->where('transactions.id', '!=', $currentTransactionId);
        }

        // Batasi jumlah hasil untuk menghemat memori
        $usedInOtherTransactions = $usedInOtherTransactionsQuery
            ->select('detail_transaction_product_item.product_item_id')
            ->limit(1000) // Batasi jumlah maksimum item yang diambil
            ->pluck('product_item_id')
            ->toArray();

        // Gabungkan semua ID yang harus dikeluarkan dengan cara yang lebih efisien
        $excludedIds = array_unique(array_merge($usedInCurrentRepeater, $usedInOtherTransactions));

        $resultIds = [];
        $displayParts = [];

        foreach ($bundling->bundlingProducts as $bundlingProduct) {
            $requiredQty = $quantity * ($bundlingProduct->quantity ?? 1);
            $product = $bundlingProduct->product;

            // Get currently assigned items for this product in this detail transaction
            $currentlyAssignedIds = [];
            if ($currentDetailTransactionId) {
                $currentlyAssignedIds = DB::table('detail_transaction_product_item')
                    ->join('product_items', 'detail_transaction_product_item.product_item_id', '=', 'product_items.id')
                    ->where('detail_transaction_product_item.detail_transaction_id', $currentDetailTransactionId)
                    ->where('product_items.product_id', $product->id)
                    ->select('detail_transaction_product_item.product_item_id')
                    ->pluck('product_item_id')
                    ->toArray();
            }

            // Ambil serial number yang tersedia untuk produk ini dengan query yang lebih efisien
            $query = DB::table('product_items')
                ->where('product_id', $product->id);

            if (!empty($excludedIds)) {
                $query->where(function ($q) use ($excludedIds, $currentlyAssignedIds) {
                    $q->whereNotIn('id', $excludedIds);
                    if (!empty($currentlyAssignedIds)) {
                        $q->orWhereIn('id', $currentlyAssignedIds);
                    }
                });
            }

            $items = $query->limit($requiredQty + count($currentlyAssignedIds))
                ->select('id', 'serial_number')
                ->get();

            $ids = $items->pluck('id')->toArray();
            $serials = $items->pluck('serial_number')->toArray();

            if (!empty($serials)) {
                // Batasi jumlah serial yang ditampilkan untuk menghemat memori
                if (count($serials) > 5) {
                    $displaySerials = array_slice($serials, 0, 5);
                    $displayParts[] = "{$product->name} (" . implode(', ', $displaySerials) . ", +" . (count($serials) - 5) . " more)";
                } else {
                    $displayParts[] = "{$product->name} (" . implode(', ', $serials) . ")";
                }
            }

            $resultIds = array_merge($resultIds, $ids);
        }

        $finalDisplay = empty($displayParts) ? '-' : implode(', ', $displayParts);

        return [
            'ids' => $resultIds,
            'display' => $finalDisplay,
        ];
    }
    public static function resolveProductOrBundlingSelection($state, \Filament\Forms\Set $set, Get $get, ?array $allDetailTransactions)
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

            // Auto-assign all available items for bundling
            $set('productItems', $result['ids'] ?? []);
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
        $duration = (int)($get('duration') ?? 1);

        $service = new PromoCalculationService();
        $result = $service->calculateDiscount($promoId, $totalBeforeDiscount, $duration);

        return (int)($result['discountAmount'] ?? 0);
    }

    /**
     * Get detailed discount calculation using service
     */
    protected static function getDiscountCalculationDetails(Get $get, int $totalBeforeDiscount): array
    {
        $promoId = $get('promo_id');
        $duration = (int)($get('duration') ?? 1);

        $service = new PromoCalculationService();
        return $service->calculateDiscount($promoId, $totalBeforeDiscount, $duration);
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
                                                if ($detailTransaction && $detailTransaction->productItems && $detailTransaction->productItems->isNotEmpty()) {
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
                                                if ($detailTransaction && $detailTransaction->productItems && $detailTransaction->productItems->isNotEmpty()) {
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
                                ->helperText(function (Get $get) {
                                    $promoId = $get('promo_id');
                                    if (!$promoId) return 'Pilih promo untuk melihat detail diskon';

                                    $promo = \App\Models\Promo::find($promoId);
                                    if (!$promo || !is_array($promo->rules) || !isset($promo->rules[0])) {
                                        return 'Promo tidak valid atau tidak memiliki aturan yang benar';
                                    }

                                    $rule = $promo->rules[0]; // Use first rule as per simplified form
                                    $helperText = match ($promo->type) {
                                        'percentage' => 'Diskon ' . ($rule['percentage'] ?? 0) . '% dari total' .
                                            (!empty($rule['days']) ? ' (berlaku pada: ' . implode(', ', $rule['days']) . ')' : ''),
                                        'nominal' => 'Potongan tetap Rp ' . number_format($rule['nominal'] ?? 0, 0, ',', '.') .
                                            (!empty($rule['days']) ? ' (berlaku pada: ' . implode(', ', $rule['days']) . ')' : ''),
                                        'day_based' => 'Sewa ' . ($rule['group_size'] ?? 1) . ' hari bayar ' . ($rule['pay_days'] ?? 1) . ' hari',
                                        default => 'Promo tersedia'
                                    };

                                    return new HtmlString('<strong>Kode: ' . $promo->name . '</strong><br>' . $helperText);
                                })
                                ->columnSpanFull(),

                            Placeholder::make('total_before_discount')
                                ->label('Total Sebelum Diskon - Breakdown')
                                ->content(function (Get $get) {
                                    $details = $get('detailTransactions');
                                    $duration = max(1, (int)($get('duration') ?? 1));

                                    if (!$details || !is_array($details)) {
                                        return new HtmlString('<div style="color: #6b7280; font-style: italic;">Tidak ada produk dipilih</div>');
                                    }

                                    $breakdown = [];
                                    $totalBeforeDiscount = 0;

                                    foreach ($details as $item) {
                                        $isBundling = (bool)($item['is_bundling'] ?? false);
                                        $customId = $isBundling ? ($item['bundling_id'] ?? '') : ($item['product_id'] ?? '');

                                        if (!$customId) continue;

                                        $name = '';
                                        $unitPrice = 0;
                                        $quantity = (int)($item['quantity'] ?? 1);

                                        if ($isBundling) {
                                            $bundling = \App\Models\Bundling::find($customId);
                                            if ($bundling) {
                                                $name = $bundling->name;
                                                $unitPrice = (int)$bundling->price;
                                            }
                                        } else {
                                            $product = \App\Models\Product::find($customId);
                                            if ($product) {
                                                $name = $product->name;
                                                $unitPrice = (int)$product->price;
                                            }
                                        }

                                        if ($name && $unitPrice > 0) {
                                            $subtotal = $unitPrice * $quantity * $duration;
                                            $breakdown[] = "{$name}: Rp " . number_format($unitPrice, 0, ',', '.') .
                                                " × {$quantity} × {$duration} hari = Rp " . number_format($subtotal, 0, ',', '.');
                                            $totalBeforeDiscount += $subtotal;
                                        }
                                    }

                                    $html = '<div style="background: #f8fafc; padding: 12px; border-radius: 6px; border: 1px solid #e2e8f0;">';

                                    if (!empty($breakdown)) {
                                        $html .= '<div style="margin-bottom: 8px; font-size: 12px; color: #64748b;">Detail Perhitungan:</div>';
                                        foreach ($breakdown as $line) {
                                            $html .= '<div style="font-size: 11px; margin-bottom: 4px; color: #334155;">• ' . $line . '</div>';
                                        }
                                        $html .= '<hr style="margin: 8px 0; border: none; border-top: 1px solid #e2e8f0;">';
                                        $html .= '<div style="font-weight: bold; font-size: 13px; color: #1e293b;">Total: Rp ' . number_format($totalBeforeDiscount, 0, ',', '.') . '</div>';
                                    } else {
                                        $html .= '<div style="color: #6b7280; font-style: italic;">Tidak ada produk yang valid dipilih</div>';
                                    }

                                    $html .= '</div>';

                                    return new HtmlString($html);
                                })
                                ->reactive()
                                ->columnSpanFull()
                                ->extraAttributes(['class' => 'breakdown-container']),
                            Placeholder::make('discount_given')
                                ->label('Diskon Diberikan')
                                ->content(function (Get $get) {
                                    $promoId = $get('promo_id');
                                    if (!$promoId) return 'Rp 0';

                                    $totalBeforeDiscount = static::calculateTotalBeforeDiscount($get);
                                    $discountCalculation = static::getDiscountCalculationDetails($get, $totalBeforeDiscount);

                                    $discountAmount = $discountCalculation['discountAmount'] ?? 0;
                                    $calculationDetails = $discountCalculation['calculationDetails'] ?? [];

                                    // Get explanation
                                    $service = new PromoCalculationService();
                                    $explanation = $service->getDiscountExplanation($calculationDetails);

                                    $formattedAmount = "Rp " . number_format($discountAmount, 0, ',', '.');

                                    return new HtmlString($formattedAmount . '<br><small class="text-gray-600">' . $explanation . '</small>');
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
                                ->live()
                                ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                    $grandTotal = static::calculateGrandTotal($get);
                                    $set('grand_total', $grandTotal);
                                })
                                ->default(function (Get $get) {
                                    $grandTotal = static::calculateGrandTotal($get);
                                    return $grandTotal; // Save as INT
                                }),

                            Placeholder::make('down_payment_display')
                                ->label('Down Payment (DP) - Display Only')
                                ->content(function (Get $get, $record) {
                                    // For edit mode, show existing down payment
                                    if ($record && $record->down_payment) {
                                        $dpAmount = (int)$record->down_payment;
                                        return 'Rp ' . number_format($dpAmount, 0, ',', '.') . ' (Stored)';
                                    }

                                    // For create mode, show calculated default (50% of grand total)
                                    $grandTotal = static::calculateGrandTotal($get);
                                    $defaultDp = max(0, floor($grandTotal * 0.5));
                                    return 'Rp ' . number_format($defaultDp, 0, ',', '.') . ' (50% default)';
                                })
                                ->extraAttributes([
                                    'class' => 'text-lg font-medium text-green-600'
                                ])
                                ->columnSpan(1),

                            Hidden::make('down_payment')
                                ->default(function (Get $get, $record) {
                                    // For edit mode, preserve existing down payment
                                    if ($record && $record->down_payment) {
                                        return (int)$record->down_payment;
                                    }
                                    // For create mode, calculate 50% of grand total
                                    $grandTotal = static::calculateGrandTotal($get);
                                    return max(0, floor($grandTotal * 0.5));
                                }),



                            Placeholder::make('cancellation_fee_display')
                                ->label('Cancellation Fee (50%)')
                                ->content(function (Get $get, $record) {
                                    if ($record && $record->cancellation_fee) {
                                        $cancelFee = (int)$record->cancellation_fee;
                                        return 'Rp ' . number_format($cancelFee, 0, ',', '.') . ' (Stored)';
                                    }

                                    $grandTotal = static::calculateGrandTotal($get);
                                    $cancelFee = (int) floor($grandTotal * 0.5);
                                    return 'Rp ' . number_format($cancelFee, 0, ',', '.') . ' (50% of Grand Total)';
                                })
                                ->extraAttributes(['class' => 'text-md text-gray-600'])
                                ->columnSpan(1),
                            Hidden::make('cancellation_fee')
                                ->default(function (Get $get, $record) {
                                    $grandTotal = static::calculateGrandTotal($get);
                                    $cancelFee = (int) floor($grandTotal * 0.5);
                                    return max(0, $cancelFee); // Simpan sebagai INT

                                })
                                ->extraAttributes(['class' => 'text-md text-gray-600'])
                                ->columnSpan(1),


                            // TextInput::make('editable_down_payment_admin')
                            //     ->label('Edit DP (Admin Only)')
                            //     ->numeric()
                            //     ->placeholder('Leave empty to use default')
                            //     ->helperText('Only administrators can modify down payment manually')
                            //     ->visible(fn() => auth()->user()?->hasRole('admin'))
                            //     ->afterStateUpdated(function ($state, Set $set) {
                            //         if ($state && is_numeric($state)) {
                            //             $set('down_payment', (int)$state);
                            //         }
                            //     })
                            //     ->columnSpan(1),

                            TextInput::make('down_payment')
                                ->label('Down Payment (DP)')
                                ->numeric()
                                ->required()
                                ->live(debounce: 300)
                                ->prefix('Rp')
                                ->step(1000)
                                ->helperText(function (Get $get) {
                                    $grandTotal = static::calculateGrandTotal($get);
                                    $minPayment = max(0, floor($grandTotal * 0.5));
                                    return 'Minimum 50% of Grand Total: Rp ' . number_format($minPayment, 0, ',', '.') .
                                        ' - Maximum: Rp ' . number_format($grandTotal, 0, ',', '.');
                                })
                                ->minValue(function (Get $get) {
                                    $grandTotal = static::calculateGrandTotal($get);
                                    return max(0, floor($grandTotal * 0.5));
                                })
                                ->maxValue(fn(Get $get) => static::calculateGrandTotal($get))
                                ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                    $grandTotal = static::calculateGrandTotal($get);
                                    $downPayment = max(0, (int)($state ?? 0));

                                    // Ensure grand total is set
                                    $set('grand_total', $grandTotal);

                                    // Calculate remaining payment without changing booking status
                                    $remaining = max(0, $grandTotal - $downPayment);
                                    $set('remaining_payment', $remaining);
                                })
                                ->default(function (Get $get, $record) {
                                    // For edit mode, preserve existing down payment
                                    if ($record && $record->down_payment) {
                                        return (int)$record->down_payment;
                                    }
                                    // For create mode, calculate 50% of grand total
                                    $grandTotal = static::calculateGrandTotal($get);
                                    return max(0, floor($grandTotal * 0.5));
                                })
                                ->columnSpanFull(),
                            Placeholder::make('remaining_payment_display')
                                ->label('Remaining Payment (Pelunasan)')
                                ->live()
                                ->content(function (Get $get) {
                                    $grandTotal = static::calculateGrandTotal($get);
                                    $downPayment = (int)($get('down_payment') ?? 0);
                                    $remainingPayment = max(0, $grandTotal - $downPayment);

                                    $status = $remainingPayment > 0 ? 'Outstanding' : 'Fully Paid';
                                    $color = $remainingPayment > 0 ? 'text-orange-600' : 'text-green-600';

                                    return new HtmlString(
                                        '<div class="' . $color . ' font-medium text-lg">' .
                                            'Rp ' . number_format($remainingPayment, 0, ',', '.') .
                                            '<br><small>(' . $status . ')</small></div>'
                                    );
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
                                                ->live(debounce: 300)
                                                ->prefix('Rp')
                                                ->inputMode('decimal')
                                                ->step(1000)
                                                ->formatStateUsing(fn($state) => $state ? number_format($state, 0, '', '') : '')
                                                ->dehydrateStateUsing(fn($state) => (int) str_replace(',', '', $state))
                                                ->afterStateUpdated(function ($state, $set, $get) {
                                                    $grandTotal = static::calculateGrandTotal($get);
                                                    $set('../../grand_total', $grandTotal);

                                                    // Recalculate remaining payment
                                                    $downPayment = (int)($get('../../down_payment') ?? 0);
                                                    $remaining = max(0, $grandTotal - $downPayment);
                                                    $set('../../remaining_payment', $remaining);
                                                })
                                                ->columnSpan(1),
                                        ])
                                        ->columns(2)
                                        ->addActionLabel('Add Additional Service')
                                        ->defaultItems(1)
                                        ->collapsible()
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
                    // Convert search term to lowercase for case-insensitive search
                    $lowerSearchTerm = strtolower($searchTerm);

                    $query->where(function (Builder $q) use ($lowerSearchTerm) {
                        $q->whereRaw('LOWER(transactions.booking_transaction_id) LIKE ?', ["%{$lowerSearchTerm}%"])
                            ->orWhereRaw('LOWER(transactions.start_date) LIKE ?', ["%{$lowerSearchTerm}%"])
                            ->orWhereRaw('LOWER(transactions.end_date) LIKE ?', ["%{$lowerSearchTerm}%"])
                            ->orWhereHas('customer', function (Builder $q2) use ($lowerSearchTerm) {
                                $q2->whereRaw('LOWER(name) LIKE ?', ["%{$lowerSearchTerm}%"]);
                            })
                            ->orWhereHas('detailTransactions.product', function (Builder $q3) use ($lowerSearchTerm) {
                                $q3->whereRaw('LOWER(name) LIKE ?', ["%{$lowerSearchTerm}%"]);
                            })
                            ->orWhereHas('detailTransactions.bundling', function (Builder $q4) use ($lowerSearchTerm) {
                                $q4->whereRaw('LOWER(name) LIKE ?', ["%{$lowerSearchTerm}%"]);
                            })
                            ->orWhereHas('detailTransactions.productItems', function (Builder $q5) use ($lowerSearchTerm) {
                                $q5->whereRaw('LOWER(serial_number) LIKE ?', ["%{$lowerSearchTerm}%"]);
                            });
                    });
                }

                // Apply default ordering for better performance
                $query->orderBy('transactions.created_at', 'desc');

                return $query;
            })
            // ->searchable()
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
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('detailTransactions', function (Builder $q) use ($search) {
                            $q->whereHas('product', function (Builder $q2) use ($search) {
                                $q2->where('name', 'like', "%{$search}%");
                            })
                                ->orWhereHas('bundling', function (Builder $q2) use ($search) {
                                    $q2->where('name', 'like', "%{$search}%");
                                });
                        });
                    })
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
                                    if ($detail->bundling->relationLoaded('bundlingProducts')) {
                                        $bundlingDetails = [];
                                        // Log::info('Processing bundling detail', ['detail_id' => $detail->id, 'bundling_id' => $detail->bundling_id]);
                                        // Log::info('Detail productItems loaded:', ['productItems' => $detail->productItems->pluck('id', 'product_id')->toArray()]);

                                        foreach ($detail->bundling->bundlingProducts as $bundlingProductRel) {
                                            $requiredQty = $quantity * ($bundlingProductRel->quantity ?? 1);
                                            $bundlingProduct = $bundlingProductRel->product;

                                            // Get serial numbers for this product in this detail transaction
                                            $serialNumbers = [];
                                            // Log::info('Checking bundling product:', ['bundling_product_id' => $bundlingProduct->id, 'name' => $bundlingProduct->name]);

                                            if ($detail->relationLoaded('productItems') && $detail->productItems) {
                                                // Filter product items directly for the current bundling product
                                                $filteredProductItems = $detail->productItems->where('product_id', $bundlingProduct->id);
                                                foreach ($filteredProductItems as $item) {
                                                    $serialNumbers[] = static::highlightSearchTerm($item->serial_number, $searchTerm);
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
                                    if ($detail->relationLoaded('productItems') && $detail->productItems) {
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
                    ->lineClamp(null), // Remove line clamp to show all content
                // TextColumn::make('detailTransactions.productItems.serial_number')
                //     ->label('S/N')
                //     ->size(TextColumnSize::ExtraSmall)
                //     ->formatStateUsing(function ($record) {
                //         $serialNumbers = [];
                //         $searchTerm = request('tableSearch');

                //         foreach ($record->detailTransactions as $detail) {
                //             if ($detail->relationLoaded('productItems') && $detail->productItems) {
                //                 foreach ($detail->productItems as $item) {
                //                     $serialNumbers[] = static::highlightSearchTerm($item->serial_number, $searchTerm);
                //                 }
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
                    ->label('Grand Total')
                    ->size(TextColumnSize::ExtraSmall)
                    ->formatStateUsing(function (?int $state, $record): string {
                        $transactionId = $record->id ?? 'unknown';

                        Log::info("[GRAND_TOTAL_COLUMN] Processing transaction ID: {$transactionId}", [
                            'state_parameter' => $state,
                            'record_grand_total' => $record->grand_total,
                            'booking_status' => $record->booking_status ?? 'unknown'
                        ]);

                        // PRIORITY: Use database value if exists, never override
                        $grandTotal = (int) ($record->grand_total ?? 0);

                        Log::info("[GRAND_TOTAL_COLUMN] Database value check for transaction {$transactionId}", [
                            'database_grand_total' => $grandTotal,
                            'will_use_fallback' => $grandTotal <= 0
                        ]);

                        // FALLBACK ONLY: If grand_total is 0/null, calculate including additional_services
                        if ($grandTotal <= 0) {
                            Log::info("[GRAND_TOTAL_COLUMN] Using fallback calculation for transaction {$transactionId}");

                            try {
                                $grandTotal = $record->getGrandTotalWithFallback();

                                Log::info("[GRAND_TOTAL_COLUMN] Fallback calculation result for transaction {$transactionId}", [
                                    'calculated_grand_total' => $grandTotal,
                                    'additional_services_total' => $record->getTotalAdditionalServices(),
                                    'base_price' => $record->getTotalBasePrice(),
                                    'duration' => $record->duration,
                                    'discount_amount' => $record->getDiscountAmount()
                                ]);
                            } catch (\Throwable $e) {
                                Log::error("[GRAND_TOTAL_COLUMN] Error in fallback calculation for transaction {$transactionId}", [
                                    'error' => $e->getMessage(),
                                    'trace' => $e->getTraceAsString()
                                ]);
                                $grandTotal = 0;
                            }
                        } else {
                            Log::info("[GRAND_TOTAL_COLUMN] Using database value for transaction {$transactionId}", [
                                'database_value' => $grandTotal
                            ]);
                        }

                        $formatted = 'Rp ' . number_format($grandTotal, 0, ',', '.');

                        Log::info("[GRAND_TOTAL_COLUMN] Final result for transaction {$transactionId}", [
                            'final_grand_total' => $grandTotal,
                            'formatted_display' => $formatted
                        ]);

                        return $formatted;
                    })
                    ->color('success')
                    ->weight(FontWeight::Bold)
                    ->alignRight()
                    ->sortable()
                    ->searchable()
                    ->tooltip('Grand Total from database (includes additional services)'),
                TextColumn::make('down_payment')
                    ->label('down Payment')
                    ->size(TextColumnSize::ExtraSmall)
                    ->formatStateUsing(function (?int $state, $record): string {
                        $transactionId = $record->id ?? 'unknown';


                        // PRIORITY: Use database value if exists, never override
                        $downPayment = (int) ($record->down_payment ?? 0);


                        // FALLBACK ONLY: If grand_total is 0/null, calculate including additional_services

                        $formatted = 'Rp ' . number_format($downPayment, 0, ',', '.');


                        return $formatted;
                    })
                    ->color('success')
                    ->weight(FontWeight::Bold)
                    ->alignRight()
                    ->sortable()
                    ->searchable()
                    ->tooltip('Down payment from database'),
                TextColumn::make('remaining_payment')
                    ->label('sisa')
                    ->size(TextColumnSize::ExtraSmall)
                    ->formatStateUsing(function (?int $state, $record): string {
                        // Ambil nilai dari database, fallback ke 0 jika null
                        $remainingPayment = (int) ($record->remaining_payment ?? 0);

                        // Jika sisa = 0, tampilkan "LUNAS"
                        if ($remainingPayment === 0) {
                            return 'LUNAS';
                        }

                        // Format Rupiah jika > 0
                        return 'Rp ' . number_format($remainingPayment, 0, ',', '.');
                    })
                    ->color('success')
                    ->weight(FontWeight::Bold)
                    ->alignRight()
                    ->sortable()
                    ->searchable()
                    ->tooltip('Down payment from database'),




                TextColumn::make('cancellation_fee')
                    ->label('Cancel Fee')
                    ->size(TextColumnSize::ExtraSmall)
                    ->formatStateUsing(function (?int $state, $record): string {
                        $transactionId = $record->id ?? 'unknown';

                        Log::info("[CANCELLATION_FEE_COLUMN] Processing transaction ID: {$transactionId}", [
                            'state_parameter' => $state,
                            'record_cancellation_fee' => $record->cancellation_fee,
                            'record_grand_total' => $record->grand_total,
                            'booking_status' => $record->booking_status ?? 'unknown'
                        ]);

                        // PRIORITY: Use stored cancellation fee if available, default to 0 if null
                        if ($state && $state > 0) {
                            Log::info("[CANCELLATION_FEE_COLUMN] Using stored cancellation fee for transaction {$transactionId}", [
                                'stored_cancellation_fee' => $state
                            ]);

                            $formatted = 'Rp ' . number_format($state, 0, ',', '.');

                            Log::info("[CANCELLATION_FEE_COLUMN] Final result (stored) for transaction {$transactionId}", [
                                'final_cancellation_fee' => $state,
                                'formatted_display' => $formatted
                            ]);

                            return $formatted;
                        }

                        Log::info("[CANCELLATION_FEE_COLUMN] No stored cancellation fee, calculating for transaction {$transactionId}");

                        // FALLBACK: Calculate 50% of grand_total (including additional_services)
                        $grandTotal = (int) ($record->grand_total ?? 0);

                        Log::info("[CANCELLATION_FEE_COLUMN] Grand total check for transaction {$transactionId}", [
                            'database_grand_total' => $grandTotal,
                            'will_use_fallback_calculation' => $grandTotal <= 0
                        ]);

                        // If grand_total is 0/null, use non-override calculation
                        if ($grandTotal <= 0) {
                            Log::info("[CANCELLATION_FEE_COLUMN] Using fallback grand total calculation for transaction {$transactionId}");

                            try {
                                $grandTotal = $record->getGrandTotalWithFallback();

                                Log::info("[CANCELLATION_FEE_COLUMN] Fallback grand total result for transaction {$transactionId}", [
                                    'fallback_grand_total' => $grandTotal,
                                    'includes_additional_services' => $record->getTotalAdditionalServices()
                                ]);
                            } catch (\Throwable $e) {
                                Log::error("[CANCELLATION_FEE_COLUMN] Error in fallback grand total calculation for transaction {$transactionId}", [
                                    'error' => $e->getMessage(),
                                    'trace' => $e->getTraceAsString()
                                ]);
                                $grandTotal = 0;
                            }
                        } else {
                            Log::info("[CANCELLATION_FEE_COLUMN] Using database grand total for transaction {$transactionId}", [
                                'database_grand_total' => $grandTotal
                            ]);
                        }

                        $cancellationFee = (int) floor($grandTotal * 0.5);
                        $formatted = 'Rp ' . number_format($cancellationFee, 0, ',', '.');

                        Log::info("[CANCELLATION_FEE_COLUMN] Final result (calculated) for transaction {$transactionId}", [
                            'final_grand_total' => $grandTotal,
                            'calculated_cancellation_fee' => $cancellationFee,
                            'percentage' => '50%',
                            'formatted_display' => $formatted
                        ]);

                        return $formatted;
                    })
                    ->color('danger')
                    ->weight(FontWeight::Medium)
                    ->alignRight()
                    ->sortable()
                    ->tooltip('50% of Grand Total (database value or calculated)'),
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

                Tables\Filters\SelectFilter::make('product_or_bundling')
                    ->label('Product / Bundling')
                    ->options(function () {
                        $products = \App\Models\Product::pluck('name', 'id')->toArray();
                        $bundlings = \App\Models\Bundling::pluck('name', 'id')->toArray();

                        // Prefix key untuk membedakan produk dan bundling
                        $productOptions = collect($products)->mapWithKeys(fn($name, $id) => ["product_{$id}" => $name])->toArray();
                        $bundlingOptions = collect($bundlings)->mapWithKeys(fn($name, $id) => ["bundling_{$id}" => $name])->toArray();

                        return array_merge($productOptions, $bundlingOptions);
                    })
                    ->query(function (Builder $query, array $data) {
                        $query->where(function (Builder $q) use ($data) {
                            foreach ($data as $key) {
                                if (str_starts_with($key, 'product_')) {
                                    $id = str_replace('product_', '', $key);
                                    $q->orWhereHas('detailTransactions.product', fn($q2) => $q2->where('id', $id));
                                } elseif (str_starts_with($key, 'bundling_')) {
                                    $id = str_replace('bundling_', '', $key);
                                    $q->orWhereHas('detailTransactions.bundling', fn($q2) => $q2->where('id', $id));
                                }
                            }
                        });
                    })
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('date_range')
                    ->form([
                        DatePicker::make('start_date')
                            ->label('Start Date')
                            ->default(Carbon::now()->subDays(10)),
                        DatePicker::make('end_date')
                            ->label('End Date')
                            ->default(Carbon::now()->addDays(10)),
                    ])
                    ->default([
                        'start_date' => Carbon::now()->subDays(10)->toDateString(),
                        'end_date' => Carbon::now()->addDays(10)->toDateString(),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['start_date']) && !empty($data['end_date'])) {
                            // Show transactions that overlap with the selected date range
                            $query->where(function (Builder $q) use ($data) {
                                $q->whereBetween('start_date', [$data['start_date'], $data['end_date']])
                                  ->orWhereBetween('end_date', [$data['start_date'], $data['end_date']])
                                  ->orWhere(function (Builder $q2) use ($data) {
                                      $q2->where('start_date', '<=', $data['start_date'])
                                         ->where('end_date', '>=', $data['end_date']);
                                  });
                            });
                        } elseif (!empty($data['start_date'])) {
                            // Show transactions that start on or after the start date OR end after the start date
                            $query->where(function (Builder $q) use ($data) {
                                $q->where('start_date', '>=', $data['start_date'])
                                  ->orWhere('end_date', '>=', $data['start_date']);
                            });
                        } elseif (!empty($data['end_date'])) {
                            // Show transactions that end on or before the end date OR start before the end date
                            $query->where(function (Builder $q) use ($data) {
                                $q->where('end_date', '<=', $data['end_date'])
                                  ->orWhere('start_date', '<=', $data['end_date']);
                            });
                        }
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (! $data['start_date'] && ! $data['end_date']) {
                            return null;
                        }

                        if ($data['start_date'] && $data['end_date']) {
                            return 'From ' . Carbon::parse($data['start_date'])->toFormattedDateString()
                                . ' to ' . Carbon::parse($data['end_date'])->toFormattedDateString();
                        }

                        if ($data['start_date']) {
                            return 'From ' . Carbon::parse($data['start_date'])->toFormattedDateString();
                        }

                        return 'Until ' . Carbon::parse($data['end_date'])->toFormattedDateString();
                    }),
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
                ExportAction::make()
                    ->label('Export All')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->exporter(TransactionExporter::class)
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

                    ExportBulkAction::make()
                        ->label('Export Selected')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('info')
                        ->exporter(TransactionExporter::class),

                ]),
            ]);
    }
    protected function beforeSave(): void
    {
        $detailTransactions = $this->data['detailTransactions'] ?? [];
        $startDate = Carbon::parse($this->data['start_date'] ?? now());
        $endDate = Carbon::parse($this->data['end_date'] ?? now());

        foreach ($detailTransactions as $i => $detail) {
            $hasProduct = !empty($detail['product_id']);
            $hasBundling = !empty($detail['bundling_id']);
            $hasItems = !empty($detail['productItems'] ?? []);

            if ($hasProduct && !$hasBundling) {
                // Validasi ketersediaan item untuk produk individual
                if (!$hasItems) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        "detailTransactions.$i.productItems" => "Produk ini harus memiliki item (serial number) atau masuk dalam bundling.",
                    ]);
                }

                // Validasi ketersediaan item pada rentang waktu yang diminta
                $availableItems = ProductItem::where('product_id', $detail['product_id'])
                    ->whereDoesntHave('detailTransactions.transaction', function ($query) use ($startDate, $endDate) {
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
                    ->whereIn('id', $detail['productItems'])
                    ->count();

                if ($availableItems < count($detail['productItems'])) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        "detailTransactions.$i.productItems" => "Beberapa item yang dipilih tidak tersedia pada rentang waktu yang diminta.",
                    ]);
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
