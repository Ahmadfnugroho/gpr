<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TransactionRequest;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class TransactionController extends Controller
{
    public function store(TransactionRequest $request)
    {
        $validatedData = $request->validated();

        $product = Product::find($validatedData['product_id']);
        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        $duration = (int) $validatedData['duration'];
        if ($duration <= 0) {
            return response()->json(['error' => 'Invalid duration'], 400);
        }

        $bookingStatus = 'booking';

        $customer = Customer::firstOrCreate(
            ['email' => $validatedData['email']],
            ['name' => $validatedData['name']]
        );

        $transaction = Transaction::create([
            'customer_id' => $customer->id,
            'booking_status' => $bookingStatus,
            'start_date' => $validatedData['start_date'],
            'duration' => $duration,
            'note' => null,
        ]);

        if (!empty($validatedData['phone'])) {
            $customer->customerPhoneNumbers()->updateOrCreate(
                ['phone_number' => $validatedData['phone']],
                ['is_primary' => true]
            );
        }

        $transaction->detailTransactions()->create([
            'product_id' => $product->id,
            'quantity' => (int) $validatedData['quantity'],
            'price' => $product->price,
        ]);

        return response()->json($transaction->fresh(['detailTransactions']), 201);
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
        $query = Transaction::query()
            ->select([
                'id',
                'booking_transaction_id',
                'start_date',
                'end_date',
                'booking_status',
                'customer_id',
                'user_id',
                'created_at',
                'updated_at'
            ])
            ->with([
                'customer:id,name,email',
                'user:id,name,email',
                'detailTransactions.product:id,name',
                'detailTransactions.bundling:id,name',
            ]);

        // Filter by status
        $status = $request->get('status', 'active');
        if ($status === 'active') {
            $query->whereIn('booking_status', ['booking', 'paid', 'on_rented']);
        } else {
            $query->where('booking_status', $status);
        }

        // Filter by date range
        if ($request->has('start_date')) {
            $query->whereDate('start_date', '>=', $request->get('start_date'));
        }
        
        if ($request->has('end_date')) {
            $query->whereDate('end_date', '<=', $request->get('end_date'));
        }

        // Only include future and current bookings for availability
        $query->where('end_date', '>=', Carbon::today());

        // Order by start date
        $query->orderBy('start_date', 'asc');

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
            $details = $transaction->detailTransactions->map(function ($detail) {
                return [
                    'id' => $detail->id,
                    'product_id' => $detail->product_id,
                    'bundling_id' => $detail->bundling_id,
                    'quantity' => $detail->quantity,
                    'product_name' => $detail->product?->name,
                    'bundling_name' => $detail->bundling?->name,
                ];
            });

            return [
                'id' => $transaction->id,
                'booking_transaction_id' => $transaction->booking_transaction_id,
                'start_date' => $transaction->start_date,
                'end_date' => $transaction->end_date,
                'booking_status' => $transaction->booking_status,
                'customer_name' => $transaction->customer?->name ?? ($transaction->user?->name ?? 'N/A'),
                'customer_email' => $transaction->customer?->email ?? ($transaction->user?->email ?? 'N/A'),
                'details' => $details,
                'details_count' => $details->count()
            ];
        });
    }
}
