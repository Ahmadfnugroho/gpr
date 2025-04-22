<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\RelationManagers\RentalIncludeRelationManager;
use App\Filament\Resources\TransactionResource\Pages;
use App\Filament\Resources\TransactionResource\RelationManagers;
use App\Models\Bundling;
use App\Models\DetailTransaction;
use App\Models\Product;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Section;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserPhoneNumber;
use App\Services\WhatsAppService;
use BezhanSalleh\FilamentShield\Support\Utils;
use Carbon\Carbon;
use Dompdf\FrameDecorator\Text;
use Filament\Actions\ActionGroup;
use Filament\Actions\Modal\Actions\Action;
use Filament\Actions\StaticAction;
use Filament\Forms;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Actions\ActionGroup as ActionsActionGroup;
use Illuminate\Database\Eloquent\Collection;
use Filament\Support\Enums\ActionSize;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Actions\ActionGroup as TablesActionsActionGroup;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextColumn\TextColumnSize;
use Filament\Tables\Columns\TextInputColumn;
use FontLib\Table\Type\post;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;
use NunoMaduro\Collision\Adapters\Phpunit\State;
use Rmsramos\Activitylog\Actions\ActivityLogTimelineTableAction;

class Number
{
    public static function currency($amount, $currency)
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }
}
class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;
    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
    protected static ?string $navigationGroup = 'Transaction';
    protected static ?string $navigationLabel = 'Transaction';
    protected static ?int $navigationSort = 31;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('booking_transaction_id')
                    ->label('Booking Trx Id')
                    ->disabled(),

                Section::make('Data Penyewa dan Tanggal')
                    ->schema([
                        Section::make('User')
                            ->schema([
                                Forms\Components\Select::make('user_id')
                                    ->relationship('user', 'name')
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->columnSpan(1)
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        $set('user_id', $state);
                                        $user = \App\Models\User::find($state);
                                        $set('user_status', $user ? $user->status : null);
                                        $set('user_email', $user ? $user->email : null);
                                        $phoneNumber = \App\Models\UserPhoneNumber::where('user_id', $state)->first();
                                        $set('user_phone_number', $phoneNumber ? $phoneNumber->phone_number : null);
                                    }),

                                Placeholder::make('user_status')
                                    ->label('Status')
                                    ->content(function (Get $get) {

                                        $user = User::find($get('user_id'));

                                        return (string) ($user ? $user->status : '-');
                                    }),
                                Placeholder::make('user_email')
                                    ->label('Email')
                                    ->columnSpan('auto')
                                    ->content(function (Get $get) {
                                        // Jika tidak ada customId (product belum dipilih), ambil dari transaksi sebelumnya
                                        $user = User::find($get('user_id'));

                                        return (string) ($user ? $user->email : '-');
                                    }),
                                Placeholder::make('user_phone_number')
                                    ->label('No Telepon')
                                    ->content(function (Get $get) {

                                        $phoneNumbers = UserPhoneNumber::where('user_id', $get('user_id'))->pluck('phone_number')->toArray();

                                        return !empty($phoneNumbers) ? implode(', ', $phoneNumbers) : '-';
                                    }),
                            ])
                            ->columnSpan(1)
                            ->columns(2),

                        Section::make('Durasi')
                            ->schema([
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
                                        $record = $get('record');
                                        $startDate = Carbon::parse($state)->format('Y-m-d H:i:s');
                                        $duration = (int) $get('duration');

                                        if ($startDate && $duration) {
                                            $endDate = Carbon::parse($startDate)->addDays($duration - 1)->endOfDay()->format('Y-m-d H:i');

                                            $set('end_date', $endDate);
                                        }
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
                                    }),
                                Placeholder::make('end_date')
                                    ->label('End Date')
                                    ->reactive()

                                    ->content(function ($get, $set) {

                                        $startDate = Carbon::parse($get('start_date'));
                                        $duration = (int) $get('duration');


                                        if ($startDate && $duration) {
                                            $endDate = Carbon::parse($startDate->addDays($duration)->format('d M Y, H:i'));

                                            $set('end_date', $endDate);

                                            $detailTransactions = $get('DetailTransactions') ?? [];
                                            foreach ($detailTransactions as $detailTransaction) {

                                                $productId = $detailTransaction['product_id'] ?? null;
                                                $product = \App\Models\Product::find($productId);
                                                $customId = $product ? $product->custom_id : null;




                                                $productName = '';
                                                $availableQuantity = 0;
                                                $transactionId = $get('id'); // Get current transaction ID

                                                if (str_starts_with($customId, 'bundling-')) {
                                                    $bundlingId = (int) substr($customId, 9);
                                                    $bundlingProducts = \App\Models\Bundling::find($bundlingId)?->products;
                                                    $availableQuantity = 0;
                                                    foreach ($bundlingProducts as $bundlingProduct) {
                                                        $productName = $bundlingProduct->name;
                                                        $includedStatuses = ['rented', 'paid', 'pending'];
                                                        $rentedQuantity = \App\Models\DetailTransaction::where('product_id', $bundlingProduct->id)
                                                            ->whereNotIn('id', [$transactionId])

                                                            ->whereHas('transaction', function ($query) use ($startDate, $endDate, $includedStatuses) {
                                                                $query->whereIn('booking_status', $includedStatuses)
                                                                    ->where('start_date', '<=', $endDate)
                                                                    ->where('end_date', '>=', $startDate);
                                                            })
                                                            ->sum('quantity');
                                                        $availableQuantity += $bundlingProduct->quantity - $rentedQuantity;
                                                    }
                                                    if ($availableQuantity <= 0) {
                                                        return "Produk {$bundlingProducts->first()->name} tidak tersedia pada rentang tanggal {$startDate->format('d M Y, H:i')} hingga {$endDate}.";
                                                    }
                                                } elseif (str_starts_with($customId, 'produk-')) {
                                                    $productId = (int) substr($customId, 7);
                                                    $product = \App\Models\Product::find($productId);
                                                    $productName = $product?->name;
                                                    if ($product) {
                                                        $includedStatuses = ['rented', 'paid', 'pending'];
                                                        $rentedQuantity = \App\Models\DetailTransaction::where('product_id', $productId)

                                                            ->whereHas('transaction', function ($query) use ($startDate, $endDate, $includedStatuses, $transactionId) {
                                                                $query->whereIn('booking_status', $includedStatuses)
                                                                    ->whereNotIn('id', [$transactionId])
                                                                    ->where('start_date', '<=', $endDate)
                                                                    ->where('end_date', '>=', $startDate);
                                                            })
                                                            ->sum('quantity');
                                                        $availableQuantity = $product->quantity - $rentedQuantity;
                                                        // log::info('rentedQuantity: ' . $rentedQuantity);
                                                        if ($availableQuantity <= 0) {

                                                            return "Produk {$productName} tidak tersedia pada rentang tanggal {$startDate->format('d M Y, H:i')} hingga {$endDate}.";
                                                        }
                                                    } else {
                                                        return 'Produk belum diisi';
                                                    }
                                                }
                                            }

                                            return $endDate->format('d M Y, H:i');
                                        }
                                        return 'start date atau durasi belum ditentukan.';
                                    })
                                    ->columnSpanFull(),
                            ])
                            ->columnSpan(1)
                            ->columns(2),

                    ])

                    ->columns(2),


                Section::make('Daftar Produk')

                    ->schema([
                        Forms\Components\Repeater::make('DetailTransactions')
                            ->relationship()
                            ->label('Daftar Produk')
                            ->schema([

                                Section::make('Detail Produk')
                                    ->schema([

                                        Section::make('Input Detail Transaksi')
                                            ->schema([

                                                Select::make('selected_item')
                                                    ->label('Produk/Bundling')
                                                    ->searchable()
                                                    ->preload()
                                                    ->reactive()
                                                    ->options(function () {
                                                        $products = Product::query()
                                                            ->select('id', 'name')
                                                            ->get()
                                                            ->mapWithKeys(fn($p) => ["produk-{$p->id}" => $p->name]);

                                                        $bundlings = Bundling::query()
                                                            ->select('id', 'name')
                                                            ->get()
                                                            ->mapWithKeys(fn($b) => ["bundling-{$b->id}" => $b->name]);

                                                        return $products->merge($bundlings);
                                                    })
                                                    ->getOptionLabelUsing(function ($value) {
                                                        if (str_starts_with($value, 'produk-')) {
                                                            return Product::find(explode('-', $value)[1])?->name;
                                                        }
                                                        return Bundling::find(explode('-', $value)[1])?->name;
                                                    })
                                                    ->default(function ($record) {
                                                        if ($record?->product_id) return "produk-{$record->product_id}";
                                                        if ($record?->bundling_id) return "bundling-{$record->bundling_id}";
                                                        return null;
                                                    })
                                                    ->afterStateUpdated(function ($state, $set, $get) {
                                                        $productId = null;
                                                        $bundlingId = null;
                                                        $productName = '-';
                                                        $availableQuantity = 0;

                                                        if (str_starts_with($state, 'produk-')) {
                                                            $productId = substr($state, 7);
                                                            $product = Product::find($productId);
                                                            $productName = $product->name ?? '-';

                                                            // Hitung ketersediaan produk
                                                            $rented = DetailTransaction::where('product_id', $productId)
                                                                ->whereNotIn('id', [$get('id')])
                                                                ->whereHas('transaction', function ($query) use ($get) {
                                                                    $query->whereIn('booking_status', ['rented', 'paid', 'pending'])
                                                                        ->where('start_date', '<=', $get('../../end_date'))
                                                                        ->where('end_date', '>=', $get('../../start_date'));
                                                                })
                                                                ->sum('quantity');

                                                            $availableQuantity = max(($product->quantity ?? 0) - $rented, 0);
                                                        } elseif (str_starts_with($state, 'bundling-')) {
                                                            $bundlingId = substr($state, 9);
                                                            $bundling = Bundling::with('products')->find($bundlingId);
                                                            $productName = $bundling->name ?? '-';

                                                            // Hitung ketersediaan bundling
                                                            $availableQuantities = [];
                                                            foreach ($bundling->products ?? [] as $product) {
                                                                $rented = DetailTransaction::where('product_id', $product->id)
                                                                    ->whereNotIn('id', [$get('id')])
                                                                    ->whereHas('transaction', function ($query) use ($get) {
                                                                        $query->whereIn('booking_status', ['rented', 'paid', 'pending'])
                                                                            ->where('start_date', '<=', $get('../../end_date'))
                                                                            ->where('end_date', '>=', $get('../../start_date'));
                                                                    })
                                                                    ->sum('quantity');

                                                                $availableQuantities[] = max($product->quantity - $rented, 0);
                                                            }
                                                            $availableQuantity = min($availableQuantities);
                                                            $set('is_bundling', true);
                                                        } else {
                                                            $set('is_bundling', false);
                                                            if (str_starts_with($state, 'produk-')) {
                                                                $productId = (int) substr($state, 7);
                                                                $product = \App\Models\Product::find($productId);

                                                                if (!$product) {
                                                                    return 1; // Menghindari error
                                                                }

                                                                $rentedQuantity = \App\Models\DetailTransaction::where('product_id', $productId)
                                                                    ->whereHas('transaction', function ($query) use ($get) {
                                                                        $query->whereIn('booking_status', ['rented', 'paid', 'pending'])
                                                                            ->where('start_date', '<=', $get('../../end_date'))
                                                                            ->where('end_date', '>=', $get('../../start_date'));
                                                                    })
                                                                    ->sum('quantity');


                                                                $availableQuantity = min($product->quantity - $rentedQuantity, 0);
                                                            }
                                                        }

                                                        // Set nilai ke form
                                                        $set('product_id', $productId);
                                                        $set('bundling_id', $bundlingId);
                                                        $set('product_name_display', $productName);

                                                        // Notifikasi jika stok tidak tersedia
                                                        if ($availableQuantity <= 0) {
                                                            Notification::make()
                                                                ->danger()
                                                                ->title('Stok Tidak Tersedia')
                                                                ->send();
                                                        }
                                                    }),




                                                Forms\Components\TextInput::make('quantity')
                                                    ->required()
                                                    ->numeric()
                                                    ->reactive()
                                                    ->default(1)
                                                    ->minValue(1)
                                                    ->maxValue(function (Get $get) {

                                                        $customId = (int) $get('is_bundling') === 1 ? $get('bundling_id') : $get('product_id');
                                                        $startDate = Carbon::parse($get('../../start_date'));
                                                        $endDate = Carbon::parse($get('../../end_date'));
                                                        $transactionId = $get('../../id');

                                                        // Jika tidak ada produk yang dipilih, kembalikan 1 (menghindari error)
                                                        if (!$customId) {
                                                            $detailTransaction = DetailTransaction::where('id', $transactionId)
                                                                ->select('quantity')
                                                                ->first();
                                                            if ($detailTransaction && $detailTransaction->bundling_id) {
                                                                $bundlingId = $detailTransaction->quantity;
                                                            } elseif ($detailTransaction && $detailTransaction->product_id) {
                                                                $product = \App\Models\Product::find($detailTransaction->quantity);
                                                            } else {
                                                                return 0;
                                                            }
                                                        }

                                                        $includedStatuses = ['rented', 'paid', 'pending'];
                                                        $availableQuantity = 0; // Default stok

                                                        // **Handle untuk Bundling**
                                                        if ((int) $get('is_bundling') === 1) {
                                                            $bundling = \App\Models\Bundling::where('id', $customId)->first();


                                                            $availableQuantities = [];

                                                            foreach ($bundling->products as $product) {
                                                                $rentedQuantity = \App\Models\DetailTransaction::where('product_id', $product->id)
                                                                    ->whereHas('transaction', function ($query) use ($startDate, $endDate, $includedStatuses, $transactionId) {
                                                                        $query->whereIn('booking_status', $includedStatuses)
                                                                            ->where('start_date', '<=', $endDate)
                                                                            ->where('end_date', '>=', $startDate)
                                                                            ->whereNotIn('id', [$transactionId]); // Hindari transaksi yang sedang diedit
                                                                    })
                                                                    ->sum('quantity');


                                                                $available = max($product->quantity - $rentedQuantity, 0);
                                                                $availableQuantities[] = $available;
                                                            }

                                                            $availableQuantity = min($availableQuantities);
                                                        }

                                                        // **Handle untuk Produk Tunggal**
                                                        else {
                                                            $product = \App\Models\Product::find($customId);
                                                            $rentedQuantity = \App\Models\DetailTransaction::where('id', $customId)
                                                                ->whereHas('transaction', function ($query) use ($startDate, $endDate, $includedStatuses, $transactionId) {
                                                                    $query->whereIn('booking_status', $includedStatuses)
                                                                        ->where('start_date', '<=', $endDate)
                                                                        ->where('end_date', '>=', $startDate)
                                                                        ->whereNotIn('id', [$transactionId]); // Hindari transaksi yang sedang diedit
                                                                })
                                                                ->sum('quantity');
                                                            $availableQuantity = $product->quantity - $rentedQuantity;
                                                        }

                                                        // **Menghindari error: Pastikan maxValue >= 1**
                                                        if ($availableQuantity <= 0) {
                                                            Notification::make()
                                                                ->danger()
                                                                ->title('Produk tidak tersedia')
                                                                ->body('Produk yang Anda pilih tidak tersedia dalam tanggal yang dipilih.')
                                                                ->send();

                                                            return 1; // Pastikan maxValue tidak kurang dari 1 untuk menghindari error
                                                        }

                                                        return $availableQuantity;
                                                    }),




                                            ])
                                            ->columns(2)
                                            ->columnSpan(1),
                                        // Kolom pertama


                                        Section::make('Detail Pesanan')
                                            ->description('Pastikan Produk sudah sesuai')
                                            ->schema([
                                                Placeholder::make('product_name_display')
                                                    ->label('Produk')
                                                    ->reactive()
                                                    ->content(function (Get $get, Set $set) {
                                                        $transactionId = $get('id');
                                                        $customId = (int) $get('is_bundling') === 1 ? $get('bundling_id') : $get('product_id');

                                                        $productName = '';
                                                        if (!$customId) {
                                                            $detailTransaction = DetailTransaction::where('id', $transactionId)
                                                                ->select(['id', 'bundling_id', 'product_id'])
                                                                ->first();
                                                            if ($detailTransaction) {
                                                                if ($detailTransaction->bundling_id) {
                                                                    $bundlingId = $detailTransaction->bundling_id;
                                                                    $bundlingProducts = \App\Models\Bundling::find($bundlingId)?->products;
                                                                    $productName = $bundlingProducts->pluck('name')->implode(', ');
                                                                } elseif ($detailTransaction->product_id) {
                                                                    $product = \App\Models\Product::find($detailTransaction->product_id);
                                                                    $productName = $product?->name ?? '-';
                                                                }
                                                            }
                                                        } elseif ((int) $get('is_bundling') === 1) {
                                                            $bundling = \App\Models\Bundling::where('id', $customId)->first();
                                                            if ($bundling) {
                                                                $productName = $bundling->products->pluck('name')->implode(', ');
                                                            }
                                                        } else {
                                                            $product = \App\Models\Product::find($customId);
                                                            $productName = $product?->name ?? '-';
                                                        }
                                                        return $productName;
                                                        // Set nilai yang diperbarui ke dalam placeholder

                                                    })
                                                    ->columnSpanFull(),
                                                Hidden::make('product_id')
                                                    ->default(fn(Get $get): string => (string) $get('product_id')),

                                                Hidden::make('bundling_id')
                                                    ->default(fn(Get $get): string => (string) $get('bundling_id')),

                                                Placeholder::make('available_quantity_display')
                                                    ->label('Tersedia')
                                                    ->reactive()
                                                    ->content(function (Get $get, Set $set, $record) {
                                                        // log::info('record: ' . $record);
                                                        $customId = (int) $get('is_bundling') === 1 ? $get('bundling_id') : $get('product_id');
                                                        // log::info('customId: ' . $customId);
                                                        $startDate = Carbon::parse($get('../../start_date'));
                                                        // log::info('startDate: ' . $startDate);
                                                        $endDate = Carbon::parse($get('../../end_date'));
                                                        // log::info('endDate: ' . $endDate);
                                                        $transactionId = $get('id');
                                                        // log::info('transactionId: ' . $transactionId);
                                                        if (!$customId) {
                                                            $detailTransaction = DetailTransaction::where('id', $transactionId)
                                                                ->select(['available_quantity'])
                                                                ->first();
                                                            $availableQuantity = $detailTransaction?->available_quantity ?? 0;
                                                        } elseif ((int) $get('is_bundling') === 1) {
                                                            $bundling = \App\Models\Bundling::where('id', $customId)->first();
                                                            $availableQuantity = 0;
                                                            $availableQuantities = [];
                                                            foreach ($bundling->products as $product) {
                                                                $productName = $product->name;
                                                                $includedStatuses = ['rented', 'paid', 'pending'];
                                                                // Log::info('Included Statuses: ' . implode(', ', $includedStatuses));

                                                                $rentedQuantity = \App\Models\DetailTransaction::where('product_id', $product->id)

                                                                    ->whereNotIn('id', [$transactionId])

                                                                    ->whereHas('transaction', function ($query) use ($startDate, $endDate, $includedStatuses) {
                                                                        $query->whereIn('booking_status', $includedStatuses)

                                                                            ->where('start_date', '<=', $endDate)
                                                                            ->where('end_date', '>=', $startDate);
                                                                    })
                                                                    ->sum('quantity');

                                                                $availableQuantity += $product->quantity - $rentedQuantity;
                                                                $set('available_quantity', max($product->quantity - $rentedQuantity, 0));


                                                                $availableQuantities[] = new \Illuminate\Support\HtmlString("<br>{$product->name}: <strong>{$availableQuantity}</strong> unit");

                                                                if ($availableQuantity <= 0) {
                                                                    $availableQuantities[] = new \Illuminate\Support\HtmlString("<br><span style='color:red'>{$product->name} tidak tersedia pada rentang tanggal {$startDate->format('Y-m-d')} hingga {$endDate->format('Y-m-d')}.</span>");
                                                                }
                                                            }

                                                            return new \Illuminate\Support\HtmlString(implode('', $availableQuantities));
                                                        } else {
                                                            $product = \App\Models\Product::find($customId);
                                                            $productName = $product?->name;

                                                            if ($product) {
                                                                $includedStatuses = ['rented', 'paid', 'pending'];

                                                                $rentedQuantity = \App\Models\DetailTransaction::where('product_id', $customId)
                                                                    ->whereHas('transaction', function ($query) use ($startDate, $endDate, $includedStatuses) {
                                                                        $query->whereIn('booking_status', $includedStatuses)
                                                                            ->where('start_date', '<=', $endDate)
                                                                            ->where('end_date', '>=', $startDate);
                                                                    })
                                                                    ->sum('quantity');
                                                                // log::info((string) \App\Models\DetailTransaction::where('product_id', $customId)->toSql());
                                                                // Jika tidak ada data yang cocok, $rentedQuantity akan bernilai 0
                                                                // Log::info('Rented Quantity: ' . $rentedQuantity);
                                                                $availableQuantity = $product->quantity - $rentedQuantity;
                                                                // log::info('rentedQuantity: ' . $rentedQuantity);
                                                                if ($availableQuantity <= 0) {
                                                                    return new \Illuminate\Support\HtmlString("<span style='color:red'>Produk {$productName} tidak tersedia pada rentang tanggal {$startDate->format('Y-m-d')} hingga {$endDate->format('Y-m-d')}.</span><br>");
                                                                }
                                                                $set('available_quantity', $availableQuantity);

                                                                return new \Illuminate\Support\HtmlString("Produk {$productName}: <strong>{$availableQuantity} unit</strong>");
                                                            } else {

                                                                return 'Produk belum diisi';
                                                            }
                                                        }
                                                    })
                                                    ->columnSpan(1),

                                                Placeholder::make('price')
                                                    ->label('Harga')
                                                    ->content(function (Get $get, Set $set) {
                                                        $transactionId = $get('id');
                                                        $customId = (int) $get('is_bundling') === 1 ? $get('bundling_id') : $get('product_id');

                                                        // Jika product_id kosong, cek harga dari detail_transactions
                                                        if (!$customId) {
                                                            $detailTransaction = DetailTransaction::where('id', $transactionId)
                                                                ->value('price');
                                                            if ($detailTransaction) {
                                                                return 'Rp ' . number_format($detailTransaction, 0, ',', '.');
                                                            }
                                                        }

                                                        // Ambil harga dari customId (bundling atau produk)
                                                        if ((int) $get('is_bundling') === 1) {
                                                            $bundling = \App\Models\Bundling::where('id', $customId)
                                                                ->value('price') ?? 0;
                                                        } else {
                                                            $product = \App\Models\Product::find($customId);
                                                            if ($product) {
                                                                $product = $product->price ?? 0;
                                                            } else {
                                                                $product = 0;
                                                            }
                                                        }

                                                        $price = (int) ($bundling ?? $product);
                                                        $set('price', $price);

                                                        return 'Rp ' . number_format($price, 0, ',', '.');
                                                    })


                                                    ->columnSpan(1),

                                                Placeholder::make('total_price_placeholder')
                                                    ->label('Total')
                                                    ->reactive()
                                                    ->content(function ($state, Get $get, Set $set) {

                                                        $customId = (int) $get('is_bundling') === 1 ? $get('bundling_id') : $get('product_id');

                                                        $transactionId = $get('id');

                                                        // Jika product_id kosong, cek harga dari detail_transactions
                                                        if (!$customId) {
                                                            // Jika tidak ada customId (product belum dipilih), ambil dari transaksi sebelumnya
                                                            $detailTransaction = DetailTransaction::where('id', $transactionId)
                                                                ->select(['id', 'bundling_id', 'product_id', 'available_quantity', 'total_price'])
                                                                ->first();
                                                            if ($detailTransaction && $detailTransaction->bundling_id) {
                                                                $bundlingId = $detailTransaction->bundling_id;
                                                                $bundlingProducts = \App\Models\Bundling::find($bundlingId)?->products;
                                                            } elseif ($detailTransaction && $detailTransaction->product_id) {
                                                                $product = \App\Models\Product::find($detailTransaction->product_id);
                                                            }
                                                            $totalAmount = $detailTransaction?->total_price ?? 0;

                                                            return 'Rp ' . number_format($totalAmount, 0, ',', '.');
                                                        }
                                                        if ((int) $get('is_bundling') === 1) {
                                                            $bundling = \App\Models\Bundling::where('custom_id', $customId)->first();
                                                        } else {
                                                            $product = \App\Models\Product::where('custom_id', $customId)->first();
                                                        }

                                                        $productPrice = 0;
                                                        if ((int) $get('is_bundling') === 1) {
                                                            $bundlingProducts = \App\Models\Bundling::find($customId);

                                                            $productPrice = $bundlingProducts ? $bundlingProducts->price : 0;
                                                        } else {
                                                            $product = \App\Models\Product::find($customId);

                                                            $productPrice = $product ? $product->price : 0;
                                                        }


                                                        $quantity = $get('quantity') ?? 0;

                                                        $totalAmount = $quantity * $productPrice;

                                                        $set('total_before_discount', $totalAmount);
                                                        $set('total_price', $totalAmount);




                                                        return Number::currency($totalAmount, 'IDR');
                                                    })
                                                    ->columnSpan(1),
                                                Placeholder::make('available_quantity')
                                                    ->content(fn(Get $get): string => (string) $get('available_quantity')),

                                                Hidden::make('price')
                                                    ->default(function (Get $get, Set $set) {
                                                        $transactionId = $get('id');
                                                        $customId = (int) $get('is_bundling') === 1 ? $get('bundling_id') : $get('product_id');

                                                        // Jika product_id kosong, cek harga dari detail_transactions
                                                        if (!$customId) {
                                                            $detailTransaction = DetailTransaction::where('id', $transactionId)
                                                                ->value('price');
                                                            if ($detailTransaction) {
                                                                return 'Rp ' . number_format($detailTransaction, 0, ',', '.');
                                                            }
                                                        }

                                                        // Ambil harga dari customId (bundling atau produk)
                                                        if ((int) $get('is_bundling') === 1) {
                                                            $bundling = \App\Models\Bundling::where('id', $customId)
                                                                ->value('price') ?? 0;
                                                        } else {
                                                            $product = \App\Models\Product::find($customId);
                                                            if ($product) {
                                                                $product = $product->price ?? 0;
                                                            } else {
                                                                $product = 0;
                                                            }
                                                        }

                                                        $price = (int) ($bundling ?? $product);
                                                        $set('price', $price);

                                                        return $price;
                                                    }),



                                                Hidden::make('total_price')
                                                    ->default(fn(Get $get): string => (string) $get('total_price')),






                                            ])
                                            ->columnSpan(1)
                                            ->columns(3),














                                    ])
                                    ->columns(2),



                            ])->addActionLabel('Tambah Produk'),










                    ]),

                Section::make('Keterangan')
                    ->schema([

                        Section::make('Pembayaran')
                            ->schema([
                                Forms\Components\Select::make('promo_id')
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
                                    ->reactive()
                                    ->content(function (Get $get) {
                                        $total = 0;
                                        $duration = (int) ($get('duration') ?? 1);

                                        $repeaters = $get('DetailTransactions');
                                        $record = $get('record');
                                        if (!$repeaters) {
                                            return Number::currency($total, 'IDR');
                                        }

                                        foreach ($repeaters as $key => $repeater) {
                                            $total += (int) $get('DetailTransactions.' . $key . '.total') +
                                                (int) $get('DetailTransactions.' . $key . '.total_price');
                                        }

                                        return Number::currency($total * $duration, 'IDR');
                                    }),
                                Placeholder::make('discount_given')
                                    ->label('Diskon Diberikan')
                                    ->content(function (Get $get) {
                                        $total = 0;
                                        $promoId = $get('promo_id');
                                        $duration = (int) ($get('duration') ?? 1);
                                        $repeaters = $get('DetailTransactions');

                                        if (!$repeaters) {
                                            return Number::currency(0, 'IDR');
                                        }

                                        foreach ($repeaters as $key => $repeater) {
                                            $total += (int) $get('DetailTransactions.' . $key . '.total') +
                                                (int) $get('DetailTransactions.' . $key . '.total_price');
                                        }

                                        $promo = \App\Models\Promo::find($promoId);
                                        if (!$promo) {
                                            return Number::currency(0, 'IDR');
                                        }

                                        $rules = $promo->rules;
                                        $nominalDiscount = 0;

                                        if ($promo->type === 'day_based') {
                                            $groupSize = isset($rules[0]['group_size']) ? (int) $rules[0]['group_size'] : 1;
                                            $payDays = isset($rules[0]['pay_days']) ? (int) $rules[0]['pay_days'] : $groupSize;

                                            $discountedDays = (int) ($duration / $groupSize) * $payDays;
                                            $remainingDays = $duration % $groupSize;
                                            $daysToPay = $discountedDays + $remainingDays;

                                            $nominalDiscount = ($total * $duration) - ($total * $daysToPay);
                                        } elseif ($promo->type === 'percentage') {
                                            $percentage = isset($rules[0]['percentage']) ? (float) $rules[0]['percentage'] : 0;
                                            $nominalDiscount = ($total * $duration) * ($percentage / 100);
                                        } elseif ($promo->type === 'nominal') {
                                            $nominal = isset($rules[0]['nominal']) ? (float) $rules[0]['nominal'] : 0;
                                            $nominalDiscount = min($nominal, $total * $duration);
                                        }

                                        return Number::currency((int) $nominalDiscount, 'IDR');
                                    }),
                                Placeholder::make('grand_total')
                                    ->label('Grand Total')
                                    ->content(function (Get $get, Set $set) {
                                        $total = 0;
                                        $promoId = $get('promo_id');
                                        $duration = (int) ($get('duration') ?? 1);
                                        $repeaters = $get('DetailTransactions') ?? [];

                                        foreach ($repeaters as $key => $repeater) {
                                            $total += (int) ($get('DetailTransactions.' . $key . '.total') ?? 0) +
                                                (int) ($get('DetailTransactions.' . $key . '.total_price') ?? 0);
                                        }

                                        $promo = \App\Models\Promo::find($promoId);
                                        if (!$promo) {
                                            $grandTotal = (int) $total * $duration;
                                            $set('grand_total', (int) $grandTotal);
                                            return Number::currency($grandTotal, 'IDR');
                                        }

                                        $rules = $promo->rules;
                                        $grandTotal = (int) $total * $duration;

                                        if ($promo->type === 'day_based') {
                                            $groupSize = $rules[0]['group_size'] ?? 1;
                                            $payDays = $rules[0]['pay_days'] ?? $groupSize;

                                            $discountedDays = (int) ($duration / $groupSize) * $payDays;
                                            $remainingDays = $duration % $groupSize;
                                            $daysToPay = $discountedDays + $remainingDays;

                                            $grandTotal = (int) $total * $daysToPay;
                                        } elseif ($promo->type === 'percentage') {
                                            $percentage = $rules[0]['percentage'] ?? 0;
                                            $grandTotal = ((int) $total * $duration) - (((int) $total * $duration) * ($percentage / 100));
                                        } elseif ($promo->type === 'nominal') {
                                            $nominal = $rules[0]['nominal'] ?? 0;
                                            $grandTotal = ((int) $total * $duration) - min($nominal, (int) $total * $duration);
                                        }


                                        $set('grand_total', (int) $grandTotal);
                                        $set('Jumlah_tagihan', intval($grandTotal));


                                        return Number::currency($grandTotal, 'IDR');
                                    })
                                    ->reactive(),

                                Hidden::make('grand_total')
                                    ->default(fn(Get $get): string => (string) $get('grand_total')),
                                Forms\Components\TextInput::make('down_payment')
                                    ->label('Jumlah Pembayaran/DP')
                                    ->required()
                                    ->numeric()
                                    ->reactive()
                                    ->default(fn(Get $get): int => $get('grand_total') ? intval($get('grand_total') * 0.5) : 0)

                                    ->minValue(fn(Get $get) => $get('grand_total') ? intval($get('grand_total') * 0.5) : 0)
                                    ->maxValue(fn(Get $get) => $get('grand_total') ? intval($get('grand_total')) : 0),

                                Forms\Components\Placeholder::make('remaining_payment')
                                    ->label('Pelunasan')
                                    ->content(function (Get $get, Set $set) {
                                        $remainingPayment = (int) $get('grand_total') - (int) $get('down_payment');
                                        $set('remaining_payment', $remainingPayment);
                                        return $remainingPayment === 0 ? 'LUNAS' : Number::currency($remainingPayment, 'IDR');
                                    }),



                                Forms\Components\Hidden::make('remaining_payment')
                                    ->default(fn(Get $get): string => (string) $get('remaining_payment')),







                            ])
                            ->columnSpan(1)
                            ->columns(3),
                        Section::make('Status dan Note')
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
                                    ->afterStateUpdated(fn(Set $set, Get $get, string $state) => match ($state) {
                                        'paid', 'rented', 'finished' => $set('down_payment', $get('grand_total')),
                                        default => null,
                                    })
                                    ->inline()
                                    ->columnSpanFull()
                                    ->grouped()
                                    ->reactive()
                                    ->helperText(function (Get $get) {
                                        $status = $get('booking_status');
                                        switch ($status) {
                                            case 'pending':
                                                return new \Illuminate\Support\HtmlString('Masih <strong style="color:red">DP</strong>  atau <strong style="color:red">belum pelunasan</strong> ');
                                            case 'paid':
                                                return new \Illuminate\Support\HtmlString('<strong style="color:green">Sewa sudah lunas</strong> tapi <strong style="color:red">barang belum diambil</strong>.');
                                            case 'rented':
                                                return new \Illuminate\Support\HtmlString('Sewa sudah  <strong style="color:blue">lunas </strong>dan barang sudah <strong style="color:blue">diambil</strong>');
                                            case 'cancelled':
                                                return new \Illuminate\Support\HtmlString('<strong style="color:red">Sewa dibatalkan.</strong>');
                                            case 'finished':
                                                return new \Illuminate\Support\HtmlString('<strong style="color:green">sudah selesai disewa dan barang sudah diterima.</strong>');
                                        }
                                    }),
                                Forms\Components\TextInput::make('note')
                                    ->label('Catatan Sewa'),

                            ])
                            ->columnSpan(1),




                    ])->columns(2),




            ]);
    }



    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                Tables\Columns\TextColumn::make('id')
                    ->size(TextColumnSize::ExtraSmall)


                    ->label('No')
                    ->wrap()

                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->wrap()
                    ->size(TextColumnSize::ExtraSmall)


                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.phone_number')
                    ->label('Phone')
                    ->wrap()
                    ->size(TextColumnSize::ExtraSmall)


                    ->searchable(),


                Tables\Columns\TextColumn::make('DetailTransactions.id')
                    ->label('Produk')
                    ->wrap()
                    ->size(TextColumnSize::ExtraSmall)


                    ->formatStateUsing(function ($record) {
                        if (!$record->DetailTransactions || $record->DetailTransactions->isEmpty()) {
                            return new HtmlString('-');
                        }

                        $detailTransactions = $record->DetailTransactions;

                        $productNames = []; // Untuk menyimpan nama produk
                        $bundlingNames = []; // Untuk menyimpan nama bundling dan produknya

                        foreach ($detailTransactions as $detailTransaction) {
                            // Jika ada product_id, ambil nama produk
                            if ($detailTransaction->product_id) {
                                $product = Product::find($detailTransaction->product_id);
                                if ($product) {
                                    $productNames[] = e($product->name); // Escape nama produk untuk keamanan
                                }
                            }

                            // Jika ada bundling_id, ambil nama bundling dan produknya
                            if ($detailTransaction->bundling_id) {
                                $bundling = Bundling::with('products')->find($detailTransaction->bundling_id);
                                if ($bundling) {
                                    $bundlingProducts = $bundling->products->pluck('name')->map(function ($name) {
                                        return e($name); // Escape nama produk
                                    })->implode('<br>'); // Gabungkan nama produk dengan <br>
                                    $bundlingNames[] = "({$bundlingProducts})"; // Tambahkan ke array bundling
                                }
                            }
                        }

                        // Gabungkan semua nama produk dan bundling
                        $allNames = array_merge($productNames, $bundlingNames);

                        // Jika ada nama, tambahkan nomor urut di setiap item
                        if (!empty($allNames)) {
                            $result = '<ol>'; // Mulai dengan tag <ol> untuk ordered list
                            foreach ($allNames as $name) {
                                $result .= "<li>{$name}</li>"; // Tambahkan setiap nama dalam tag <li>
                            }
                            $result .= '</ol>'; // Tutup tag <ol>
                        } else {
                            $result = '-'; // Jika tidak ada nama, kembalikan '-'
                        }


                        // Kembalikan sebagai HtmlString
                        return new HtmlString($result);
                    }),


                Tables\Columns\TextColumn::make('booking_transaction_id')
                    ->label('Trx Id')
                    ->wrap()
                    ->size(TextColumnSize::ExtraSmall)

                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('start_date')
                    ->label('Start')
                    ->wrap()
                    ->size(TextColumnSize::ExtraSmall)
                    ->formatStateUsing(fn(string $state): string => Carbon::parse($state)->locale('id_ID')->isoFormat('DD MMM YYYY hh:mm'))
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->label('End')
                    ->wrap()
                    ->size(TextColumnSize::ExtraSmall)
                    ->formatStateUsing(fn(string $state): string => Carbon::parse($state)->locale('id_ID')->isoFormat('DD MMM YYYY hh:mm'))
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('grand_total')
                    ->label('Total')
                    ->wrap()
                    ->size(TextColumnSize::ExtraSmall)
                    ->formatStateUsing(fn(string $state): string => Number::currency((int) $state / 1000, 'IDR') . 'K')
                    ->sortable()
                    ->searchable(),
                TextInputColumn::make('down_payment')
                    ->label('DP')
                    ->default(
                        function (Get $get, Set $set): int {
                            $downPayment = $get('grand_total') * 0.5;
                            $set('down_payment', $downPayment);

                            return $downPayment;
                        }
                    )
                    ->sortable(),
                TextColumn::make('remaining_payment')
                    ->label('Sisa')
                    ->wrap()

                    ->formatStateUsing(fn(string $state): HtmlString => new HtmlString($state == '0' ? '<strong style="color: green">LUNAS</strong>' : Number::currency((int) $state / 1000, 'IDR') . 'K'))

                    ->size(TextColumnSize::ExtraSmall)
                    ->sortable(),

                Tables\Columns\TextColumn::make('booking_status')
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
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\Action::make('pending')
                        ->icon('heroicon-o-clock') // Ikon untuk action
                        ->color('warning') // Warna action (warning biasanya kuning/orange)
                        ->label('Pending') // Label yang ditampilkan
                        ->requiresConfirmation() // Memastikan action memerlukan konfirmasi sebelum dijalankan
                        ->modalHeading('Ubah Status -> PENDING')
                        ->modalDescription(fn(): HtmlString => new HtmlString('Apakah Anda yakin ingin mengubah status booking menjadi Pending? <br> <strong style="color:red">Harap sesuaikan kolom DP, Jika sudah lunas maka action akan gagal</strong>')) // Deskripsi modal konfirmasi
                        ->modalSubmitActionLabel('Ya, Ubah Status') // Label tombol konfirmasi
                        ->modalCancelActionLabel('Batal') // Label tombol batal

                        ->action(function (Transaction $record, ?array $get = null) {
                            // Cek apakah down payment sama dengan grand total
                            $downPayment = (int) ($get['down_payment'] ?? 0);
                            $grandTotal = (int) ($get['grand_total'] ?? 0);

                            if ($downPayment === $grandTotal) {
                                // Notifikasi peringatan dan hentikan proses action
                                Notification::make()
                                    ->danger()
                                    ->title('UBAH STATUS GAGAL')
                                    ->body('Sesuaikan DP, jika sudah lunas maka statusnya adalah "Paid atau Rented atau Finished"')
                                    ->send();

                                // Gagalkan proses action
                                return;
                            }

                            // Update status booking menjadi 'pending' jika kondisi di atas tidak terpenuhi
                            $record->update(['booking_status' => 'pending']);

                            // Notifikasi sukses
                            Notification::make()
                                ->success()
                                ->title('Berhasil Mengubah Status Booking Transaksi')
                                ->send();
                        }),
                    Tables\Actions\Action::make('paid')
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
                    Tables\Actions\Action::make('cancelled')
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

                    Tables\Actions\Action::make('rented')
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

                    Tables\Actions\Action::make('finished')
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



                Tables\Actions\ViewAction::make()
                    ->icon('heroicon-o-eye')
                    ->label('')
                    ->size(ActionSize::ExtraSmall),

                Tables\Actions\EditAction::make()
                    ->icon('heroicon-o-pencil')
                    ->label('')

                    ->size(ActionSize::ExtraSmall),

                Tables\Actions\DeleteAction::make()
                    ->icon('heroicon-o-trash')
                    ->label('')

                    ->size(ActionSize::ExtraSmall),
                Tables\Actions\Action::make('Invoice')
                    ->color('success')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->label('')

                    ->url(fn(Transaction $record) => route('pdf', $record))
                    ->openUrlInNewTab()
                    ->size(ActionSize::ExtraSmall),
                Tables\Actions\Action::make('kirim_whatsapp')
                    ->label('Kirim WhatsApp')
                    ->action(function ($record) {
                        $wa = new WhatsAppService();
                        $wa->sendTextMessage($record->phone, "Halo, ini pesan dari sistem!");
                        Notification::make()
                            ->title('Pesan berhasil dikirim.')
                            ->success()
                            ->send();
                    }),




                ActivityLogTimelineTableAction::make('Activities')
                    ->timelineIcons([
                        'created' => 'heroicon-m-check-badge',
                        'updated' => 'heroicon-m-pencil-square',
                    ])
                    ->timelineIconColors([
                        'created' => 'info',
                        'updated' => 'warning',
                    ])
                    ->label('')
                    ->icon('heroicon-m-clock'),






            ])


            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->label('hapus'),
                Tables\Actions\BulkAction::make('pending')
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


                Tables\Actions\BulkAction::make('paid')
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

                Tables\Actions\BulkAction::make('cancelled')
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

                Tables\Actions\BulkAction::make('rented')
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

                Tables\Actions\BulkAction::make('finished')
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
