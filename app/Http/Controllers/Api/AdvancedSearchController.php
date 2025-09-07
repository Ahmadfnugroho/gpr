<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AdvancedSearchService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class AdvancedSearchController extends Controller
{
    protected AdvancedSearchService $searchService;

    public function __construct(AdvancedSearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    /**
     * Advanced search with Elasticsearch-like features
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function search(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'q' => 'required|string|min:1|max:255',
            'page' => 'integer|min:1|max:100',
            'limit' => 'integer|min:1|max:50',
            'category' => 'array',
            'category.*' => 'string|exists:categories,slug',
            'brand' => 'array',
            'brand.*' => 'string|exists:brands,slug',
            'type' => 'array',
            'type.*' => 'in:product,bundling',
            'price_min' => 'numeric|min:0',
            'price_max' => 'numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $query = trim($request->input('q'));
        $page = (int) $request->input('page', 1);
        $limit = (int) $request->input('limit', 20);

        $filters = [
            'category' => $request->input('category', []),
            'brand' => $request->input('brand', []),
            'type' => $request->input('type', []),
            'price_min' => $request->input('price_min'),
            'price_max' => $request->input('price_max'),
        ];

        // Remove empty filters
        $filters = array_filter($filters, function ($value) {
            return $value !== null && $value !== [] && $value !== '';
        });

        try {
            // Cache key for search results
            $cacheKey = 'advanced_search:' . md5(json_encode(compact('query', 'filters', 'page', 'limit')));

            $results = Cache::remember($cacheKey, 300, function () use ($query, $filters, $limit, $page) {
                return $this->searchService->search($query, $filters, $limit, $page);
            });

            return response()->json([
                'success' => true,
                'data' => $results['results'],
                'meta' => [
                    'total' => $results['total'],
                    'page' => $results['page'],
                    'limit' => $results['limit'],
                    'total_pages' => ceil($results['total'] / $results['limit']),
                    'has_next_page' => $results['page'] * $results['limit'] < $results['total'],
                    'has_prev_page' => $results['page'] > 1,
                ],
                'query' => $results['query'],
                'filters' => $results['filters'],
                'execution_time' => $results['execution_time'] ?? null,
            ]);
        } catch (\Exception $e) {


            return response()->json([
                'success' => false,
                'message' => 'Search failed. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Autocomplete suggestions for search
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function autocomplete(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'q' => 'required|string|min:2|max:100',
            'limit' => 'integer|min:1|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'suggestions' => [],
                'errors' => $validator->errors()
            ], 422);
        }

        $query = trim($request->input('q'));
        $limit = (int) $request->input('limit', 8);

        try {
            // Cache autocomplete results for 5 minutes
            $cacheKey = 'autocomplete:' . md5($query . $limit);

            $results = Cache::remember($cacheKey, 300, function () use ($query, $limit) {
                return $this->searchService->autocomplete($query, $limit);
            });

            return response()->json([
                'success' => true,
                'suggestions' => $results['suggestions'],
                'query' => $results['query'],
                'total' => $results['total'],
            ]);
        } catch (\Exception $e) {


            return response()->json([
                'success' => false,
                'suggestions' => [],
                'message' => 'Autocomplete failed'
            ], 500);
        }
    }

    /**
     * Get popular search suggestions
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function popularSuggestions(Request $request): JsonResponse
    {
        $limit = (int) $request->input('limit', 10);
        $limit = min($limit, 20); // Maximum 20 suggestions

        try {
            // Cache popular suggestions for 1 hour
            $cacheKey = 'popular_suggestions:' . $limit;

            $results = Cache::remember($cacheKey, 3600, function () use ($limit) {
                return $this->searchService->getPopularSuggestions($limit);
            });

            return response()->json([
                'success' => true,
                'suggestions' => $results['suggestions'],
                'total' => count($results['suggestions']),
            ]);
        } catch (\Exception $e) {


            return response()->json([
                'success' => false,
                'suggestions' => [],
                'message' => 'Failed to get popular suggestions'
            ], 500);
        }
    }

    /**
     * Clear search cache (for admin use)
     * 
     * @return JsonResponse
     */
    public function clearCache(): JsonResponse
    {
        try {
            // Clear all search-related cache
            Cache::flush(); // This clears all cache - in production, use more specific cache tags

            return response()->json([
                'success' => true,
                'message' => 'Search cache cleared successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear cache'
            ], 500);
        }
    }

    /**
     * Search statistics (for admin dashboard)
     * 
     * @return JsonResponse
     */
    public function getSearchStats(): JsonResponse
    {
        try {
            // Get basic search statistics
            $stats = [
                'total_products' => \App\Models\Product::where('status', 'available')->count(),
                'total_bundlings' => \App\Models\Bundling::where('status', 'available')->count(),
                'total_categories' => \App\Models\Category::count(),
                'total_brands' => \App\Models\Brand::count(),
                'cache_enabled' => Cache::getStore() !== null,
                'search_indexes' => [
                    'products_indexed' => true,
                    'bundlings_indexed' => true,
                    'categories_indexed' => true,
                    'brands_indexed' => true,
                ]
            ];

            return response()->json([
                'success' => true,
                'stats' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get search statistics'
            ], 500);
        }
    }
}
