<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TransactionRequest;
use App\Models\Product;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class TransactionController extends Controller
{
    public function store(TransactionRequest $request)
    {
        // Ambil data yang sudah tervalidasi
        // Mengambil data yang sudah tervalidasi
        $validatedData = $request->validated();

        // Cari produk berdasarkan ID
        $product = Product::find($validatedData['product_id']);

        // Pastikan produk ditemukan
        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        // Set status transaksi dan booking transaction ID menggunakan Transaction model
        $validatedData['status'] = 'booking';

        // Panggil generateUniqueBookingTrxId() dari model Transaction
        $transaction = new Transaction($validatedData);
        $validatedData['booking_transaction_id'] = $transaction->generateUniqueBookingTrxId();

        // Cek apakah 'duration' adalah angka valid
        $duration = (int) $validatedData['duration']; // Pastikan menjadi integer
        if ($duration <= 0) {
            return response()->json(['error' => 'Invalid duration'], 400); // Jika duration tidak valid, kembalikan error
        }

        // Set durasi dan tanggal selesai
        $startDate = new \DateTime($validatedData['started_at']);
        $interval = new \DateInterval('P' . $duration . 'D'); // Format yang benar: P{X}D
        $endDate = $startDate->add($interval);
        $validatedData['end_date'] = $endDate->format('Y-m-d'); // Format tanggal yang benar

        // Simpan transaksi baru
        $transaction = Transaction::create($validatedData);

        // Kembalikan response JSON jika berhasil
        return response()->json($transaction, 201); // Contoh response JSON
    }

    /**
     * Get active transactions for availability checking
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'status' => 'string|in:active,confirmed,pending,completed,cancelled',
                'limit' => 'integer|min:1|max:1000',
                'start_date' => 'date_format:Y-m-d',
                'end_date' => 'date_format:Y-m-d|after_or_equal:start_date',
                'product_id' => 'integer|exists:products,id',
            ]);

            // Cache key for this query
            $cacheKey = 'active_transactions_' . md5($request->getQueryString() ?? 'default');
            
            // Try to get from cache first
            $transactions = Cache::remember($cacheKey, 300, function () use ($request) { // 5 minutes cache
                return $this->buildTransactionsQuery($request)->get();
            });

            // Transform transactions for frontend
            $transformedTransactions = $this->transformTransactions($transactions);

            return response()->json([
                'success' => true,
                'data' => $transformedTransactions,
                'meta' => [
                    'total' => $transformedTransactions->count(),
                    'cached_at' => now(),
                    'cache_key' => $cacheKey
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Error fetching active transactions', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch transactions',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Build transactions query based on request parameters
     */
    private function buildTransactionsQuery(Request $request)
    {
        // Start with base query - using transactions table
        $query = DB::table('transactions as t')
            ->select([
                't.id',
                't.booking_transaction_id',
                't.started_at as start_date',
                't.end_date',
                't.status as booking_status',
                't.customer_name',
                't.created_at',
                't.updated_at'
            ]);

        // Filter by status
        $status = $request->get('status', 'active');
        if ($status === 'active') {
            $query->whereIn('t.status', ['booking', 'confirmed', 'active', 'ongoing']);
        } else {
            $query->where('t.status', $status);
        }

        // Filter by date range
        if ($request->has('start_date')) {
            $query->whereDate('t.started_at', '>=', $request->get('start_date'));
        }
        
        if ($request->has('end_date')) {
            $query->whereDate('t.end_date', '<=', $request->get('end_date'));
        }

        // Only include future and current bookings for availability
        $query->where('t.end_date', '>=', Carbon::today());

        // Order by start date
        $query->orderBy('t.started_at', 'asc');

        // Limit results
        $limit = min($request->get('limit', 100), 1000);
        $query->limit($limit);

        return $query;
    }

    /**
     * Transform transactions with their details for frontend
     */
    private function transformTransactions($transactions)
    {
        return $transactions->map(function ($transaction) {
            // For now, create details based on single product per transaction
            // This matches your current schema where each transaction has one product
            $details = collect([
                [
                    'id' => $transaction->id,
                    'product_id' => DB::table('transactions')->where('id', $transaction->id)->value('product_id'),
                    'bundling_id' => null, // No bundling support in current schema
                    'quantity' => (int) DB::table('transactions')->where('id', $transaction->id)->value('quantity'),
                ]
            ]);

            return [
                'id' => $transaction->id,
                'booking_transaction_id' => $transaction->booking_transaction_id,
                'start_date' => $transaction->start_date,
                'end_date' => $transaction->end_date,
                'booking_status' => $transaction->booking_status,
                'customer_name' => $transaction->customer_name ?? 'N/A',
                'details' => $details,
                'details_count' => $details->count()
            ];
        });
    }

    /**
     * Check availability for specific item and date range
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function checkAvailability(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'type' => 'required|string|in:product,bundling',
                'id' => 'required|integer',
                'start_date' => 'required|date_format:Y-m-d',
                'end_date' => 'required|date_format:Y-m-d|after_or_equal:start_date',
                'quantity' => 'integer|min:1|max:100'
            ]);

            $type = $request->get('type');
            $itemId = $request->get('id');
            $startDate = Carbon::parse($request->get('start_date'));
            $endDate = Carbon::parse($request->get('end_date'));
            $requestedQuantity = $request->get('quantity', 1);

            // For now, only support product checking (bundling can be added later)
            if ($type !== 'product') {
                return response()->json([
                    'success' => false,
                    'message' => 'Bundling availability checking not yet implemented'
                ], 400);
            }

            // Validate product exists
            $product = DB::table('products')->where('id', $itemId)->first();
            
            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found'
                ], 404);
            }

            // Get conflicting transactions
            $conflictingTransactions = $this->getConflictingTransactions($itemId, $startDate, $endDate);
            
            // Calculate used quantity
            $usedQuantity = $conflictingTransactions->sum('total_quantity');
            
            // Get total stock
            $totalStock = $product->quantity ?? 10; // Default to 10 if not specified
            
            // Calculate availability
            $availableQuantity = max(0, $totalStock - $usedQuantity);
            $available = $availableQuantity >= $requestedQuantity;

            // Get unavailable dates in the range
            $unavailableDates = $this->getUnavailableDates($itemId, $startDate, $endDate);

            return response()->json([
                'success' => true,
                'data' => [
                    'available' => $available,
                    'available_quantity' => $availableQuantity,
                    'total_stock' => $totalStock,
                    'used_quantity' => $usedQuantity,
                    'requested_quantity' => $requestedQuantity,
                    'conflicting_transactions' => $conflictingTransactions,
                    'unavailable_dates' => $unavailableDates,
                    'period' => [
                        'start_date' => $startDate->format('Y-m-d'),
                        'end_date' => $endDate->format('Y-m-d'),
                        'duration' => $startDate->diffInDays($endDate) + 1
                    ],
                    'item' => [
                        'id' => $product->id,
                        'name' => $product->name,
                        'type' => 'product',
                        'status' => $product->status ?? 'available'
                    ]
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Error checking availability', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to check availability',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get conflicting transactions for item and date range
     */
    private function getConflictingTransactions(int $productId, Carbon $startDate, Carbon $endDate)
    {
        return DB::table('transactions as t')
            ->select([
                't.id',
                't.booking_transaction_id',
                't.started_at as start_date',
                't.end_date',
                't.status as booking_status',
                't.customer_name',
                't.quantity as total_quantity',
                DB::raw('1 as details_count')
            ])
            ->where('t.product_id', $productId)
            ->whereIn('t.status', ['booking', 'confirmed', 'active', 'ongoing'])
            ->where(function ($query) use ($startDate, $endDate) {
                $query->where(function ($q) use ($startDate, $endDate) {
                    // Transaction starts before our end date and ends after our start date
                    $q->whereDate('t.started_at', '<=', $endDate->format('Y-m-d'))
                      ->whereDate('t.end_date', '>=', $startDate->format('Y-m-d'));
                });
            })
            ->get();
    }

    /**
     * Get unavailable dates for a product
     */
    private function getUnavailableDates(int $productId, Carbon $startDate = null, Carbon $endDate = null): array
    {
        $query = DB::table('transactions as t')
            ->select('t.started_at as start_date', 't.end_date')
            ->where('t.product_id', $productId)
            ->whereIn('t.status', ['booking', 'confirmed', 'active', 'ongoing'])
            ->where('t.end_date', '>=', Carbon::today()->format('Y-m-d'));

        if ($startDate && $endDate) {
            $query->where(function ($q) use ($startDate, $endDate) {
                $q->whereDate('t.started_at', '<=', $endDate->format('Y-m-d'))
                  ->whereDate('t.end_date', '>=', $startDate->format('Y-m-d'));
            });
        }

        $transactions = $query->get();
        
        $unavailableDates = [];
        
        foreach ($transactions as $transaction) {
            $current = Carbon::parse($transaction->start_date);
            $end = Carbon::parse($transaction->end_date);
            
            while ($current <= $end) {
                $unavailableDates[] = $current->format('Y-m-d');
                $current->addDay();
            }
        }

        return array_unique($unavailableDates);
    }
}
