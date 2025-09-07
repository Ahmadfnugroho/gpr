<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AvailabilityService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class AvailabilityController extends Controller
{
    protected AvailabilityService $availabilityService;

    public function __construct(AvailabilityService $availabilityService)
    {
        $this->availabilityService = $availabilityService;
    }

    /**
     * Check availability for a single item
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function check(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:product,bundling',
            'id' => 'required|integer|min:1',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
            'quantity' => 'integer|min:1|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $type = $request->input('type');
        $id = (int) $request->input('id');
        $startDate = Carbon::parse($request->input('start_date'));
        $endDate = Carbon::parse($request->input('end_date'));
        $quantity = (int) $request->input('quantity', 1);

        try {
            // Cache key for availability check
            $cacheKey = "availability:{$type}:{$id}:{$startDate->toDateString()}:{$endDate->toDateString()}:{$quantity}";
            
            $result = Cache::remember($cacheKey, 300, function () use ($type, $id, $startDate, $endDate, $quantity) {
                return $this->availabilityService->checkAvailability($type, $id, $startDate, $endDate, $quantity);
            });

            return response()->json([
                'success' => true,
                'data' => $result,
                'cached' => Cache::has($cacheKey)
            ]);

        } catch (\Exception $e) {
            \Log::error('Availability check failed', [
                'type' => $type,
                'id' => $id,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'quantity' => $quantity,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Availability check failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Check availability for multiple items (bulk check)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function checkMultiple(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'checks' => 'required|array|min:1|max:20',
            'checks.*.type' => 'required|in:product,bundling',
            'checks.*.id' => 'required|integer|min:1',
            'checks.*.start_date' => 'required|date|after_or_equal:today',
            'checks.*.end_date' => 'required|date|after:checks.*.start_date',
            'checks.*.quantity' => 'integer|min:1|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $checks = $request->input('checks');

        try {
            $results = $this->availabilityService->checkMultipleAvailability($checks);

            return response()->json([
                'success' => true,
                'data' => $results,
                'summary' => [
                    'total_checks' => count($checks),
                    'available_items' => count(array_filter($results, fn($r) => $r['available'])),
                    'unavailable_items' => count(array_filter($results, fn($r) => !$r['available'])),
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Multiple availability check failed', [
                'checks_count' => count($checks),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Multiple availability check failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get unavailable dates for calendar display
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getUnavailableDates(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:product,bundling',
            'id' => 'required|integer|min:1',
            'start_date' => 'date|after_or_equal:today',
            'end_date' => 'date|after:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $type = $request->input('type');
        $id = (int) $request->input('id');
        $startDate = $request->has('start_date') ? Carbon::parse($request->input('start_date')) : null;
        $endDate = $request->has('end_date') ? Carbon::parse($request->input('end_date')) : null;

        try {
            // Cache key for unavailable dates
            $cacheKey = "unavailable_dates:{$type}:{$id}:" . 
                       ($startDate ? $startDate->toDateString() : 'null') . ':' . 
                       ($endDate ? $endDate->toDateString() : 'null');
            
            $unavailableDates = Cache::remember($cacheKey, 600, function () use ($type, $id, $startDate, $endDate) {
                return $this->availabilityService->getUnavailableDates($type, $id, $startDate, $endDate);
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'unavailable_dates' => $unavailableDates,
                    'total_unavailable_days' => count($unavailableDates),
                    'item' => [
                        'type' => $type,
                        'id' => $id,
                    ],
                    'period' => [
                        'start_date' => $startDate?->toDateString(),
                        'end_date' => $endDate?->toDateString(),
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Get unavailable dates failed', [
                'type' => $type,
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get unavailable dates',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Check if a specific date range is available
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function isDateRangeAvailable(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:product,bundling',
            'id' => 'required|integer|min:1',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $type = $request->input('type');
        $id = (int) $request->input('id');
        $startDate = Carbon::parse($request->input('start_date'));
        $endDate = Carbon::parse($request->input('end_date'));

        try {
            $isAvailable = $this->availabilityService->isDateRangeAvailable($type, $id, $startDate, $endDate);

            return response()->json([
                'success' => true,
                'data' => [
                    'available' => $isAvailable,
                    'item' => [
                        'type' => $type,
                        'id' => $id,
                    ],
                    'period' => [
                        'start_date' => $startDate->toDateString(),
                        'end_date' => $endDate->toDateString(),
                        'duration' => $startDate->diffInDays($endDate) + 1,
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Date range availability check failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Validate cart availability (for checkout)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function checkCartAvailability(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'items' => 'required|array|min:1|max:20',
            'items.*.cart_item_id' => 'string',
            'items.*.type' => 'required|in:product,bundling',
            'items.*.id' => 'required|integer|min:1',
            'items.*.name' => 'string',
            'items.*.start_date' => 'required|date|after_or_equal:today',
            'items.*.end_date' => 'required|date|after:items.*.start_date',
            'items.*.quantity' => 'required|integer|min:1|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $cartItems = $request->input('items');

        try {
            $results = $this->availabilityService->checkCartAvailability($cartItems);

            return response()->json([
                'success' => true,
                'data' => $results,
                'ready_for_checkout' => $results['all_available']
            ]);

        } catch (\Exception $e) {
            \Log::error('Cart availability check failed', [
                'items_count' => count($cartItems),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Cart availability check failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get availability statistics
     * 
     * @return JsonResponse
     */
    public function getStats(): JsonResponse
    {
        try {
            $stats = $this->availabilityService->getAvailabilityStats();

            return response()->json([
                'success' => true,
                'data' => $stats,
                'generated_at' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get availability statistics',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Clear availability cache
     * 
     * @return JsonResponse
     */
    public function clearCache(): JsonResponse
    {
        try {
            // Clear availability-related cache patterns
            Cache::flush(); // In production, use more specific cache tags
            
            return response()->json([
                'success' => true,
                'message' => 'Availability cache cleared successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear availability cache'
            ], 500);
        }
    }
}
