<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransactionResource\Number;
use App\Filament\Resources\TransactionResource\Pages;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

use App\Models\Bundling;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserPhoneNumber;
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
use Filament\Notifications\Collection;
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

use Filament\Tables\Columns\TextInputColumn;
use Rmsramos\Activitylog\Actions\ActivityLogTimelineTableAction;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Transaction';
    protected static ?string $navigationLabel = 'Transaction';
    protected static ?int $navigationSort = 31;
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

        if (!$productId || !$currentUuid) {
            return [];
        }

        // Ambil product_item_id dari tabel pivot yang sudah digunakan dalam repeater saat ini
        $usedInCurrentRepeater = collect($allDetailTransactions)
            ->filter(fn($row) => isset($row['uuid']) && $row['uuid'] !== $currentUuid)
            ->flatMap(fn($row) => $row['productItems'] ?? [])
            ->unique()
            ->values()
            ->all();


        // Ambil product_item_id dari transaksi lain yang aktif
        $usedInOtherTransactions = DetailTransactionProductItem::whereHas('detailTransaction.transaction', function ($query) use ($startDate, $endDate) {
            $query->where(function ($q) use ($startDate, $endDate) {
                // Periksa apakah rentang tanggal tumpang tindih
                $q->whereBetween('start_date', [$startDate, $endDate]) // Rentang mulai di dalam rentang target
                    ->orWhereBetween('end_date', [$startDate, $endDate]) // Rentang akhir di dalam rentang target
                    ->orWhere(function ($q2) use ($startDate, $endDate) {
                        // Rentang target sepenuhnya berada di dalam rentang transaksi
                        $q2->where('start_date', '<=', $startDate)
                            ->where('end_date', '>=', $endDate);
                    });
            });
        })
            ->pluck('product_item_id')
            ->toArray();


        // Gabungkan semua ID yang harus dikeluarkan
        $excludedIds = array_unique(array_merge($usedInCurrentRepeater, $usedInOtherTransactions));


        // Ambil serial number yang tersedia
        $availableIds = ProductItem::query()
            ->where('product_id', $productId)
            ->whereNotIn('id', $excludedIds)
            ->limit($quantity)
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
        array $allDetailTransactions
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
            ->filter(fn($row) => isset($row['uuid']) && $row['uuid'] !== $currentUuid)
            ->flatMap(fn($row) => $row['productItems'] ?? [])
            ->unique()
            ->values()
            ->all();


        // Ambil product_item_id dari transaksi lain yang aktif
        $usedInOtherTransactions = DetailTransactionProductItem::whereHas('detailTransaction.transaction', function ($query) use ($startDate, $endDate) {
            $query->where(function ($q) use ($startDate, $endDate) {
                // Periksa apakah rentang tanggal tumpang tindih
                $q->whereBetween('start_date', [$startDate, $endDate]) // Rentang mulai di dalam rentang target
                    ->orWhereBetween('end_date', [$startDate, $endDate]) // Rentang akhir di dalam rentang target
                    ->orWhere(function ($q2) use ($startDate, $endDate) {
                        // Rentang target sepenuhnya berada di dalam rentang transaksi
                        $q2->where('start_date', '<=', $startDate)
                            ->where('end_date', '>=', $endDate);
                    });
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


            // Ambil serial number yang tersedia untuk produk ini
            $items = ProductItem::query()
                ->where('product_id', $product->id)
                ->whereNotIn('id', $excludedIds)
                ->limit($requiredQty)
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
                $allDetailTransactions ?? []
            );

            $set('productItems', $result['ids'] ?? []);
        } else {
            // Jika produk tunggal dipilih
            $set('product_id', (int) $id);
            $set('bundling_id', null);

            // Resolve serial numbers untuk produk tunggal
            $serials = \App\Filament\Resources\TransactionResource::resolveAvailableProductSerials($get, $set);

            $set('productItems', $serials ?? []); // Perbarui nilai productItems
        }
    }


    /**
     * Calculate total price before discount from detail transactions
     */
    protected static function calculateTotalBeforeDiscount(Get $get): int
    {
        $details = collect($get('detailTransactions') ?? []);
        
        return $details->sum(function ($item) {
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
     * Calculate grand total (total before discount - discount + duration)
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
        
        // Calculate discount
        $discountAmount = static::calculateDiscountAmount($get, $totalBeforeDiscount);
        
        // Final total
        return max(0, $totalWithDuration - $discountAmount);
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
            $set('booking_status', 'cancelled');
            return;
        }
        
        if ($downPayment <= 0) {
            $set('booking_status', 'cancelled');
        } elseif ($downPayment >= $grandTotal) {
            // Full payment - keep existing status if it's rented/finished, otherwise set to paid
            $currentStatus = request()->input('booking_status');
            if (!in_array($currentStatus, ['rented', 'finished'])) {
                $set('booking_status', 'paid');
            }
        } else {
            // Partial payment
            $minPayment = max(0, floor($grandTotal * 0.5));
            if ($downPayment >= $minPayment) {
                $set('booking_status', 'pending');
            } else {
                $set('booking_status', 'cancelled');
            }
        }
    }

    public static function form(Form $form): Form
    {

        return $form->schema([

            TextInput::make('booking_transaction_id')
                ->label('Booking Trx Id')
                ->disabled()
                ->columnSpanFull(),
            Grid::make('Durasi')
                ->schema([
                    Select::make('user_id')
                        ->relationship('user', 'name')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->live()
                        ->columnSpan(1)
                        ->afterStateUpdated(function ($state, callable $set) {
                            $set('user_id', $state);
                            $user = \App\Models\User::find($state);
                            $set('user_status', $user ? $user->status : '');
                            $set('user_email', $user ? $user->email : '');
                            $phoneNumber = \App\Models\UserPhoneNumber::where('user_id', $state)->first();
                            $set('user_phone_number', $phoneNumber ? $phoneNumber->phone_number : '');
                        })
                        ->helperText(function (Get $get) {
                            $user = User::find($get('user_id'));

                            if (! $user) {
                                return new HtmlString('Pilih pengguna untuk melihat detail.');
                            }

                            $statusColor = match ($user->status) {
                                'active' => 'text-green-600',
                                'blacklist' => 'text-red-600',
                                default => 'text-gray-600',
                            };

                            return new HtmlString(
                                "Status: <strong class=\"{$statusColor}\">{$user->status}</strong><br>" .
                                    "Email: <strong>{$user->email}</strong><br>" .
                                    "Phone: <strong>{$user->phone_number}</strong>"
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
                        ->afterStateUpdated(function ($state, $get, $set) {
                            $set('is_bundling', false);
                            $set('bundling_id', null);
                            $set('product_id', null);
                            $set('productItems', []); // 
                            $startDate = Carbon::parse($state)->format('Y-m-d H:i:s');
                            $duration = (int) $get('duration');

                            if ($startDate && $duration) {
                                $endDate = Carbon::parse($startDate)->addDays($duration - 1)->endOfDay()->format('Y-m-d H:i');

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
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {

                            $startDate = $get('start_date');
                            $duration = (int) $state;
                            if ($startDate && $duration) {
                                $endDate = Carbon::parse($startDate)->addDays($duration - 1)->endOfDay()->format('Y-m-d H:i:s');

                                $set('end_date', $endDate);
                            }
                            $allDetailTransactions = $get('../../detailTransactions') ?? [];
                            \App\Filament\Resources\TransactionResource::resolveProductOrBundlingSelection($state, $set, $get, $allDetailTransactions);
                        }),
                    Placeholder::make('end_date')
                        ->label('End Date')
                        ->reactive()
                        ->content(function ($get, $set) {
                            // Validasi start_date dan duration
                            $startDateRaw = $get('start_date');
                            $duration = (int) $get('duration');

                            if (!$startDateRaw || !$duration) {
                                return 'Tanggal mulai atau durasi belum diisi.';
                            }

                            try {
                                $startDate = Carbon::parse($startDateRaw);
                            } catch (\Exception $e) {
                                return 'Format tanggal tidak valid.';
                            }

                            // Hitung end_date
                            $endDate = $startDate->copy()->addDays($duration - 1)->endOfDay();
                            $set('end_date', $endDate->toDateTimeString());

                            // Validasi ketersediaan produk/bundling
                            $detailTransactions = $get('detailTransactions') ?? [];

                            foreach ($detailTransactions as $detailTransaction) {
                                $customId = $detailTransaction['product_id'] ?? '';

                                if (!filled($customId)) continue;

                                if (str_starts_with($customId, 'bundling-')) {
                                    $bundlingId = (int) substr($customId, 9);
                                    $bundling = \App\Models\Bundling::find($bundlingId);

                                    if ($bundling) {
                                        $quantity = $detailTransaction['quantity'] ?? 1;
                                        $available = $bundling->getAvailableQuantityForPeriod($startDate, $endDate, $quantity);

                                        if ($available <= 0) {
                                            Notification::make()
                                                ->danger()
                                                ->title('Bundling Tidak Tersedia')
                                                ->body("Bundling tidak tersedia dari {$startDate->format('d M')} hingga {$endDate->format('d M')}.")
                                                ->send();

                                            return "Bundling tidak tersedia dari {$startDate->format('d M')} hingga {$endDate->format('d M')}.";
                                        }
                                    }
                                } elseif (str_starts_with($customId, 'produk-')) {
                                    $productId = (int) substr($customId, 7);
                                    $product = \App\Models\Product::find($productId);

                                    if ($product) {
                                        $available = $product->getAvailableQuantityForPeriod($startDate, $endDate);

                                        if ($available <= 0) {
                                            Notification::make()
                                                ->danger()
                                                ->title('Produk Tidak Tersedia')
                                                ->body("Produk {$product->name} tidak tersedia dari {$startDate->format('d M')} hingga {$endDate->format('d M')}.")
                                                ->send();

                                            return "Produk {$product->name} tidak tersedia dari {$startDate->format('d M')} hingga {$endDate->format('d M')}.";
                                        }
                                    }
                                }
                            }

                            // Return format tanggal akhir
                            return $endDate->format('d M Y, H:i');
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
                            Hidden::make('is_bundling')->default(false),
                            Hidden::make('bundling_id'),
                            Hidden::make('product_id'),
                            Grid::make()
                                ->columns([
                                    'sm' => 1,
                                    'md' => 2,
                                    'lg' => 6,
                                ])->schema([
                                    Select::make('selection_key')
                                        ->label('Pilih Produk/Bundling')
                                        ->searchable()
                                        ->options(function () {
                                            $products = Product::pluck('name', 'id')->mapWithKeys(fn($name, $id) => ["produk-{$id}" => $name]);
                                            $bundlings = Bundling::pluck('name', 'id')->mapWithKeys(fn($name, $id) => ["bundling-{$id}" => $name]);
                                            return $products->merge($bundlings)->toArray();
                                        })
                                        ->live(debounce: 300)

                                        // Trigger saat awal data dimuat
                                        ->afterStateHydrated(function ($state, Set $set, Get $get) {
                                            if (!$state) return;

                                            [$type, $id] = explode('-', $state);
                                            $isBundling = $type === 'bundling';
                                            $set('is_bundling', $isBundling);

                                            $uuid = $get('uuid');
                                            $startDate = $get('../../start_date') ? Carbon::parse($get('../../start_date')) : now();
                                            $endDate = $get('../../end_date') ? Carbon::parse($get('../../end_date')) : now();
                                            $all = $get('../../detailTransactions') ?? [];

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
                                                    $all
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
                                        ->columnSpan(2),
                                    TextInput::make('quantity')
                                        ->label('Jumlah')
                                        ->numeric()
                                        ->default(1)
                                        ->minValue(1)
                                        ->live(debounce: 300)
                                        ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                            $allDetailTransactions = $get('../../detailTransactions') ?? [];
                                            \App\Filament\Resources\TransactionResource::resolveProductOrBundlingSelection($state, $set, $get, $allDetailTransactions);
                                        })
                                        ->columnSpan(1),

                                    CheckboxList::make('productItems')
                                        ->label('Serial Numbers Tersedia')
                                        ->visible(function (Get $get) {
                                            return !is_null($get('selection_key'));
                                        })
                                        ->relationship(
                                            'productItems',
                                            'serial_number',
                                            function (Builder $query, callable $get, CheckboxList $component) {
                                                $startDate = $get('../../start_date') ? Carbon::parse($get('../../start_date')) : now();
                                                $endDate = $get('../../end_date') ? Carbon::parse($get('../../end_date')) : now();
                                                $productId = $get('product_id');
                                                $bundlingId = $get('bundling_id');
                                                $quantity = (int) ($get('quantity') ?? 1);
                                                $uuid = $get('uuid');
                                                $detailTransactions = $get('../../detailTransactions') ?? [];

                                                // Ambil semua product_item_id yang sudah digunakan dalam repeater saat ini
                                                $usedInCurrentRepeater = collect($detailTransactions)
                                                    ->filter(fn($row) => isset($row['uuid']) && $row['uuid'] !== $uuid)
                                                    ->flatMap(fn($row) => $row['productItems'] ?? [])
                                                    ->unique()
                                                    ->values()
                                                    ->all();


                                                // Ambil semua product_item_id yang sudah digunakan di transaksi lain
                                                $usedInOtherTransactions = DetailTransactionProductItem::whereHas('detailTransaction.transaction', function ($q) use ($startDate, $endDate) {
                                                    $q->where(function ($q) use ($startDate, $endDate) {
                                                        $q->whereBetween('start_date', [$startDate, $endDate])
                                                            ->orWhereBetween('end_date', [$startDate, $endDate]);
                                                    });
                                                })
                                                    ->pluck('product_item_id')
                                                    ->toArray();


                                                // Gabungkan semua ID yang harus dikeluarkan
                                                $excludedIds = array_unique(array_merge($usedInCurrentRepeater, $usedInOtherTransactions));



                                                if ($productId) {
                                                    // Untuk produk tunggal
                                                    $availableIds = \App\Filament\Resources\TransactionResource::resolveAvailableProductSerials($get, fn() => null);
                                                    $query->whereIn('product_items.id', $availableIds);
                                                } elseif ($bundlingId) {
                                                    // Untuk bundling
                                                    $result = \App\Filament\Resources\TransactionResource::resolveBundlingProductSerialsDisplay(
                                                        (int) $bundlingId,
                                                        $quantity,
                                                        $startDate,
                                                        $endDate,
                                                        $uuid,
                                                        $detailTransactions
                                                    );
                                                    $query->whereIn('product_items.id', $result['ids']);
                                                }

                                                // Pastikan untuk tidak menyertakan ID yang sudah dikeluarkan
                                                $query->whereNotIn('product_items.id', $excludedIds);
                                            }
                                        )->saveRelationshipsUsing(function (CheckboxList $component, ?array $state, callable $get) {
                                            $detailTransaction = $component->getModelInstance();
                                            if (!empty($state)) {
                                                $detailTransaction->productItems()->sync($state);
                                            } else {
                                                $detailTransaction->productItems()->detach();
                                            }
                                        })
                                        ->default(function (Get $get) {
                                            $startDate = $get('../../start_date') ? Carbon::parse($get('../../start_date')) : now();
                                            $endDate = $get('../../end_date') ? Carbon::parse($get('../../end_date')) : now();
                                            $productId = $get('product_id');
                                            $bundlingId = $get('bundling_id');
                                            $quantity = (int) ($get('quantity') ?? 1);
                                            $uuid = $get('uuid');
                                            $detailTransactions = $get('../../detailTransactions') ?? [];

                                            if ($productId) {
                                                return \App\Filament\Resources\TransactionResource::resolveAvailableProductSerials($get);
                                            } elseif ($bundlingId) {
                                                $result = \App\Filament\Resources\TransactionResource::resolveBundlingProductSerialsDisplay(
                                                    (int) $bundlingId,
                                                    $quantity,
                                                    $startDate,
                                                    $endDate,
                                                    $uuid,
                                                    $detailTransactions
                                                );
                                                return $result['ids'] ?? [];
                                            }

                                            return [];
                                        })
                                        ->columns(2)
                                        ->reactive()
                                        ->bulkToggleable()
                                        ->columnSpan(3),

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
                                ->rules([
                                    function (Get $get) {
                                        $grandTotal = static::calculateGrandTotal($get);
                                        if ($grandTotal <= 0) return ['required', 'min:0'];
                                        
                                        $min = max(0, floor($grandTotal * 0.5)); // 50% minimum

                                        return [
                                            'required',
                                            'numeric',
                                            'min:0',
                                            function ($attribute, $value, $fail) use ($min, $grandTotal) {
                                                $value = (int)$value;
                                                if ($value < $min) {
                                                    $fail("Nilai harus minimal Rp " . number_format($min, 0, ',', '.') . " (50% dari total)");
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
                                    return 'Pembayaran minimal 50%: Rp ' . number_format($minPayment, 0, ',', '.') . 
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
                                        $set('booking_status', 'cancelled');
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
                                ->step(1000),
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
                                    'pending' => 'pending',
                                    'paid' => 'paid',
                                    'cancelled' => 'cancelled',
                                    'rented' => 'rented',
                                    'finished' => 'finished',
                                ])
                                ->icons([
                                    'pending' => 'heroicon-o-clock',
                                    'cancelled' => 'heroicon-o-x-circle',
                                    'rented' => 'heroicon-o-shopping-bag',
                                    'finished' => 'heroicon-o-check',
                                    'paid' => 'heroicon-o-banknotes',
                                ])
                                ->colors([
                                    'pending' => 'warning',
                                    'cancelled' => 'danger',
                                    'rented' => 'info',
                                    'finished' => 'success',
                                    'paid' => 'success',
                                ])
                                ->inline()
                                ->columnSpanFull()
                                ->grouped()
                                ->reactive()
                                ->default('pending')
                                ->helperText(function (Get $get) {
                                    $status = $get('booking_status');
                                    switch ($status) {
                                        case 'pending':
                                            return new HtmlString('Masih <strong style="color:red">DP</strong> atau <strong style="color:red">belum pelunasan</strong>');
                                        case 'paid':
                                            return new HtmlString('<strong style="color:green">Sewa sudah lunas</strong> tapi <strong style="color:red">barang belum diambil</strong>.');
                                        case 'rented':
                                            return new HtmlString('Sewa sudah <strong style="color:blue">lunas</strong> dan barang sudah <strong style="color:blue">diambil</strong>');
                                        case 'cancelled':
                                            return new HtmlString('<strong style="color:red">Sewa dibatalkan.</strong>');
                                        case 'finished':
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

                                    // Atur down_payment berdasarkan status
                                    match ($state) {
                                        'paid', 'rented', 'finished' => $set('down_payment', $grandTotal),
                                        'cancelled' => $set('down_payment', 0),
                                        default => $set('down_payment', max(0, (int)($grandTotal / 2))),
                                    };

                                    $isUpdating = false;
                                    $set('remaining_payment', max(0, $grandTotal - $get('down_payment')));
                                    $set('grand_total', $grandTotal);
                                }),
                            TextInput::make('note')
                                ->label('Catatan Sewa'),



                        ])
                        ->columnSpan(1)
                        ->columns([
                            'sm' => 1,
                            'md' => 2,
                        ]),
                ])->columns(2),
        ]);
    }
    public static function getEagerLoadRelations(): array
    {
        return [
            'user.userPhoneNumbers',
            'detailTransactions.product',
            'detailTransactions.bundling.products', // jika bundling membutuhkan eager load produk
            'detailTransactions.productItem',
            'promo',
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('booking_transaction_id')
                    ->label('ID')
                    ->searchable()
                    ->size(TextColumnSize::ExtraSmall)

                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Nama')
                    ->size(TextColumnSize::ExtraSmall)

                    ->searchable(),
                TextColumn::make('user.phone_number')
                    ->label('WA')
                    ->formatStateUsing(fn($state) => '+62' . ltrim($state, '0'))
                    ->url(fn($record) => 'https://wa.me/62' . ltrim($record->user->phone_number, '0'))
                    ->openUrlInNewTab()
                    ->color('success')
                    ->size(TextColumnSize::ExtraSmall)

                    ->copyable(),
                TextColumn::make('product_info')
                    ->label('Prdk')
                    ->limit(20)
                    ->size(TextColumnSize::ExtraSmall)
                    ->formatStateUsing(function ($record) {
                        $products = [];
                        
                        foreach ($record->detailTransactions as $detail) {
                            if ($detail->bundling_id && $detail->bundling) {
                                $products[] = $detail->bundling->name . ' (Bundle)';
                            } elseif ($detail->product_id && $detail->product) {
                                $products[] = $detail->product->name;
                            }
                        }
                        
                        return implode(', ', array_unique($products)) ?: 'N/A';
                    })
                    ->wrap(),
                TextColumn::make('detailTransactions.productItems.serial_number')
                    ->label('S/N')
                    ->limit(20)
                    ->size(TextColumnSize::ExtraSmall)

                    ->wrap(),
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
                    ->default(fn(Transaction $record): int => (int)($record->grand_total / 2))
                    ->sortable(),

                TextColumn::make('remaining_payment')
                    ->label('Sisa')
                    ->size(TextColumnSize::ExtraSmall)
                    ->formatStateUsing(fn(string $state): HtmlString => new HtmlString(
                        $state == '0' ? '<strong style="color: green">LUNAS</strong>' : 'Rp ' . number_format((int) $state, 0, ',', '.')
                    ))
                    ->sortable(),

                TextColumn::make('booking_status')
                    ->label('')
                    ->wrap()
                    ->size(TextColumnSize::ExtraSmall)
                    ->icon(fn(string $state): string => match ($state) {
                        'pending' => 'heroicon-o-clock',
                        'cancelled' => 'heroicon-o-x-circle',
                        'rented' => 'heroicon-o-shopping-bag',
                        'finished' => 'heroicon-o-check',
                        'paid' => 'heroicon-o-banknotes',
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'pending' => 'warning',
                        'cancelled' => 'danger',
                        'rented' => 'info',
                        'finished' => 'success',
                        'paid' => 'success',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('user.name'),
                Tables\Filters\SelectFilter::make('booking_status')
                    ->options([
                        'pending' => 'pending',
                        'cancelled' => 'cancelled',
                        'rented' => 'rented',
                        'finished' => 'finished',
                        'paid' => 'paid',
                    ]),
            ])
            ->actions([
                BulkActionGroup::make([
                    Action::make('pending')
                        ->icon('heroicon-o-clock') // Ikon untuk action
                        ->color('warning') // Warna action (warning biasanya kuning/orange)
                        ->label('Pending') // Label yang ditampilkan
                        ->requiresConfirmation() // Memastikan action memerlukan konfirmasi sebelum dijalankan
                        ->modalHeading('Ubah Status -> PENDING')
                        ->modalDescription(fn(): HtmlString => new HtmlString('Apakah Anda yakin ingin mengubah status booking menjadi Pending? <br> <strong style="color:red">Harap sesuaikan kolom DP, Jika sudah lunas maka action akan gagal</strong>')) // Deskripsi modal konfirmasi
                        ->modalSubmitActionLabel('Ya, Ubah Status') // Label tombol konfirmasi
                        ->modalCancelActionLabel('Batal') // Label tombol batal

                        ->action(function (Transaction $record) {
                            // Ambil data dari record langsung
                            $downPayment = (int) ($record->down_payment ?? 0);
                            $grandTotal = (int) ($record->grand_total ?? 0);

                            // Validasi: cek apakah DP sama dengan grand total
                            if ($downPayment === $grandTotal) {
                                Notification::make()
                                    ->danger()
                                    ->title('UBAH STATUS GAGAL')
                                    ->body('Sesuaikan DP, jika sudah lunas maka statusnya adalah "Paid", "Rented", atau "Finished"')
                                    ->send();

                                return; // Hentikan eksekusi jika kondisi tidak sesuai
                            }

                            try {
                                // Update status booking menjadi 'pending'
                                $record->update(['booking_status' => 'pending']);

                                // Notifikasi sukses
                                Notification::make()
                                    ->success()
                                    ->title('Berhasil Mengubah Status Booking Transaksi')
                                    ->send();
                            } catch (\Exception $e) {
                                // Tangani error saat update

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
                    Action::make('cancelled')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->label('cancelled')
                        ->requiresConfirmation()
                        ->action(function (Transaction $record) {
                            $record->update([
                                'booking_status' => 'cancelled',
                                'down_payment' => $record->grand_total, // Set down_payment sama dengan grand_total

                            ]);



                            Notification::make()
                                ->success()
                                ->title('Berhasil Mengubah Status Booking Transaksi')
                                ->send();
                        }),

                    Action::make('rented')
                        ->icon('heroicon-o-shopping-bag')
                        ->color('info')
                        ->label('rented')
                        ->requiresConfirmation()
                        ->action(function (Transaction $record) {
                            $record->update([
                                'booking_status' => 'rented',
                                'down_payment' => $record->grand_total, // Set down_payment sama dengan grand_total

                            ]);



                            Notification::make()
                                ->success()
                                ->title('Berhasil Mengubah Status Booking Transaksi')
                                ->send();
                        }),

                    Action::make('finished')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->label('finished')
                        ->requiresConfirmation()
                        ->action(function (Transaction $record) {
                            $record->update(['booking_status' => 'finished']);



                            Notification::make()
                                ->success()
                                ->title('Berhasil Mengubah Status Booking Transaksi')
                                ->send();
                        })
                ])
                    ->label('status')
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
                    Action::make('Invoice')
                        ->color('success')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->label('invoice')

                        ->url(fn(Transaction $record) => route('pdf', $record))
                        ->openUrlInNewTab()
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
                    BulkAction::make('pending')
                        ->icon('heroicon-o-clock')
                        ->color('warning')
                        ->label('Pending')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()


                        ->action(function (Collection $records) {
                            $records->each->update(['booking_status' => 'pending']);
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
                            $records->each->update(['booking_status' => 'paid']);
                            Notification::make()
                                ->success()
                                ->title('Berhasil Mengubah Status Booking Transaksi')
                                ->send();
                        }),

                    BulkAction::make('cancelled')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->label('cancelled')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()


                        ->action(function (Collection $records) {
                            $records->each->update(['booking_status' => 'cancelled']);
                            Notification::make()
                                ->success()
                                ->title('Berhasil Mengubah Status Booking Transaksi')
                                ->send();
                        }),

                    BulkAction::make('rented')
                        ->icon('heroicon-o-shopping-bag')
                        ->color('info')
                        ->label('rented')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()


                        ->action(function (Collection $records) {
                            $records->each->update(['booking_status' => 'rented']);
                            Notification::make()
                                ->success()
                                ->title('Berhasil Mengubah Status Booking Transaksi')
                                ->send();
                        }),

                    BulkAction::make('finished')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->label('finished')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()


                        ->action(function (Collection $records) {
                            $records->each->update(['booking_status' => 'finished']);
                            Notification::make()
                                ->success()
                                ->title('Berhasil Mengubah Status Booking Transaksi')
                                ->send();
                        }),



                ]),
            ]);
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
                            // Select::make('serial_numbers')
                            //     ->label('Pilih Serial Number')
                            //     ->visible(fn(Get $get): bool => !$get('is_bundling'))
                            //     ->searchable()
                            //     ->preload()
                            //     ->reactive()
                            //     ->options(function (Get $get) {
                            //         // Ambil product_id dari field lain di repeater ini
                            //         $productId = $get('product_id');
                            //         if (!$productId) return [];

                            //         // Ambil start_date & end_date dari form
                            //         $startDate = $get('../../start_date') ? Carbon::parse($get('../../start_date')) : now();
                            //         $endDate = $get('../../end_date') ? Carbon::parse($get('../../end_date')) : now();

                            //         // Ambil semua item produk yang tersedia di periode tersebut
                            //         $items = ProductItem::where('product_id', $productId)
                            //             ->actuallyAvailableForPeriod($startDate, $endDate)
                            //             ->pluck('serial_number', 'id');

                            //         return $items->mapWithKeys(fn($sn, $id) => [$id => $sn])->toArray();
                            //     })
                            //     ->multiple()
                            //     ->minItems(fn(Get $get) => $get('quantity') ?? 1)
                            //     ->maxItems(fn(Get $get) => $get('quantity') ?? 1)
                            //     ->helperText(fn(Get $get) => 'Pilih tepat ' . ($get('quantity') ?? 1) . ' serial number')

                            //     ->required(fn(Get $get): bool => !$get('is_bundling')),
