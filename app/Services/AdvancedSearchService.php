<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Bundling;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdvancedSearchService
{
    /**
     * Perform advanced search with Elasticsearch-like features
     */
    public function search(string $query, array $filters = [], int $limit = 20, int $page = 1): array
    {
        if (empty(trim($query))) {
            return [
                'results' => [],
                'total' => 0,
                'page' => $page,
                'limit' => $limit,
                'filters' => $filters
            ];
        }

        $searchQuery = trim($query);
        
        // Get products and bundlings with weighted scoring
        $products = $this->searchProducts($searchQuery, $filters);
        $bundlings = $this->searchBundlings($searchQuery, $filters);
        
        // Combine and sort by score
        $combined = $products->merge($bundlings);
        $sorted = $combined->sortByDesc('score')->values();
        
        // Apply pagination
        $total = $sorted->count();
        $offset = ($page - 1) * $limit;
        $paginatedResults = $sorted->slice($offset, $limit)->values();
        
        return [
            'results' => $paginatedResults->toArray(),
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'filters' => $filters,
            'query' => $searchQuery,
            'execution_time' => microtime(true) - LARAVEL_START
        ];
    }

    /**
     * Search products with scoring
     */
    private function searchProducts(string $query, array $filters = []): Collection
    {
        $productsQuery = Product::query()
            ->with([
                'category:id,name,slug',
                'brand:id,name,slug', 
                'productPhotos:id,product_id,photo'
            ])
            ->where('status', 'available'); // Only available products

        // Apply filters
        $productsQuery = $this->applyFilters($productsQuery, $filters, 'product');
        
        $products = $productsQuery->get();
        
        return $products->map(function ($product) use ($query) {
            $score = $this->calculateScore($query, [
                'name' => $product->name,
                'category' => $product->category?->name,
                'brand' => $product->brand?->name,
                'description' => $product->description ?? '',
            ]);
            
            return [
                'id' => $product->id,
                'type' => 'product',
                'name' => $product->name,
                'slug' => $product->slug,
                'price' => $product->price,
                'thumbnail' => $product->productPhotos->first()?->photo,
                'category' => $product->category ? [
                    'name' => $product->category->name,
                    'slug' => $product->category->slug,
                ] : null,
                'brand' => $product->brand ? [
                    'name' => $product->brand->name,
                    'slug' => $product->brand->slug,
                ] : null,
                'description' => $product->description,
                'score' => $score,
                'matched_fields' => $this->getMatchedFields($query, [
                    'name' => $product->name,
                    'category' => $product->category?->name,
                    'brand' => $product->brand?->name,
                ]),
                'url' => "/product/{$product->slug}",
                'display' => $product->name,
            ];
        })->filter(function ($item) {
            return $item['score'] > 0.1; // Minimum score threshold
        });
    }

    /**
     * Search bundlings with scoring
     */
    private function searchBundlings(string $query, array $filters = []): Collection
    {
        $bundlingsQuery = Bundling::query()
            ->with([
                'category:id,name,slug',
                'brand:id,name,slug',
                'bundlingPhotos:id,bundling_id,photo'
            ])
            ->where('status', 'available'); // Only available bundlings

        // Apply filters
        $bundlingsQuery = $this->applyFilters($bundlingsQuery, $filters, 'bundling');
        
        $bundlings = $bundlingsQuery->get();
        
        return $bundlings->map(function ($bundling) use ($query) {
            $score = $this->calculateScore($query, [
                'name' => $bundling->name,
                'category' => $bundling->category?->name,
                'brand' => $bundling->brand?->name,
                'description' => $bundling->description ?? '',
            ]);
            
            return [
                'id' => $bundling->id,
                'type' => 'bundling',
                'name' => $bundling->name,
                'slug' => $bundling->slug,
                'price' => $bundling->price,
                'thumbnail' => $bundling->bundlingPhotos->first()?->photo,
                'category' => $bundling->category ? [
                    'name' => $bundling->category->name,
                    'slug' => $bundling->category->slug,
                ] : null,
                'brand' => $bundling->brand ? [
                    'name' => $bundling->brand->name,
                    'slug' => $bundling->brand->slug,
                ] : null,
                'description' => $bundling->description,
                'score' => $score,
                'matched_fields' => $this->getMatchedFields($query, [
                    'name' => $bundling->name,
                    'category' => $bundling->category?->name,
                    'brand' => $bundling->brand?->name,
                ]),
                'url' => "/bundling/{$bundling->slug}",
                'display' => "📦 {$bundling->name}",
            ];
        })->filter(function ($item) {
            return $item['score'] > 0.1; // Minimum score threshold
        });
    }

    /**
     * Apply filters to query
     */
    private function applyFilters($query, array $filters, string $type)
    {
        // Category filter
        if (isset($filters['category']) && is_array($filters['category'])) {
            $query->whereHas('category', function ($q) use ($filters) {
                $q->whereIn('slug', $filters['category']);
            });
        }

        // Brand filter
        if (isset($filters['brand']) && is_array($filters['brand'])) {
            $query->whereHas('brand', function ($q) use ($filters) {
                $q->whereIn('slug', $filters['brand']);
            });
        }

        // Price range filter
        if (isset($filters['price_min'])) {
            $query->where('price', '>=', (float) $filters['price_min']);
        }
        if (isset($filters['price_max'])) {
            $query->where('price', '<=', (float) $filters['price_max']);
        }

        // Type filter (for mixed searches)
        if (isset($filters['type']) && is_array($filters['type'])) {
            if (!in_array($type, $filters['type'])) {
                $query->whereRaw('1 = 0'); // Exclude this type
            }
        }

        return $query;
    }

    /**
     * Calculate search score with weighted fields
     */
    private function calculateScore(string $query, array $fields): float
    {
        $queryLower = strtolower(trim($query));
        $score = 0.0;

        // Weight configuration (matching frontend)
        $weights = [
            'name' => 4.0,        // 40%
            'category' => 2.5,    // 25%
            'brand' => 2.5,       // 25%
            'description' => 1.0, // 10%
        ];

        foreach ($fields as $field => $text) {
            if (empty($text) || !isset($weights[$field])) {
                continue;
            }

            $fieldScore = $this->fuzzyMatchScore($queryLower, strtolower($text));
            $score += $fieldScore * $weights[$field];
        }

        return round($score, 3);
    }

    /**
     * Fuzzy matching score calculation (similar to frontend)
     */
    private function fuzzyMatchScore(string $query, string $text): float
    {
        if (empty($query) || empty($text)) {
            return 0.0;
        }

        // Exact match
        if ($query === $text) {
            return 1.0;
        }

        // Contains match
        if (strpos($text, $query) !== false) {
            $ratio = strlen($query) / strlen($text);
            return 0.8 * $ratio;
        }

        // Word-level fuzzy match
        $queryWords = array_filter(explode(' ', $query));
        $textWords = array_filter(explode(' ', $text));

        if (empty($queryWords)) {
            return 0.0;
        }

        $matchedWords = 0;
        $totalScore = 0.0;

        foreach ($queryWords as $qWord) {
            $bestWordScore = 0.0;
            
            foreach ($textWords as $tWord) {
                if (strpos($tWord, $qWord) !== false || strpos($qWord, $tWord) !== false) {
                    $bestWordScore = max($bestWordScore, 0.7);
                } elseif (strlen($qWord) >= 3) {
                    $distance = levenshtein($qWord, $tWord);
                    if ($distance <= 1) {
                        $bestWordScore = max($bestWordScore, 0.5);
                    }
                }
            }

            if ($bestWordScore > 0) {
                $matchedWords++;
                $totalScore += $bestWordScore;
            }
        }

        return $matchedWords > 0 ? ($totalScore / count($queryWords)) * 0.4 : 0.0;
    }

    /**
     * Get matched fields for highlighting
     */
    private function getMatchedFields(string $query, array $fields): array
    {
        $matched = [];
        $queryLower = strtolower($query);

        foreach ($fields as $field => $text) {
            if (empty($text)) {
                continue;
            }

            if (strpos(strtolower($text), $queryLower) !== false) {
                $matched[] = $field;
            }
        }

        return $matched;
    }

    /**
     * Generate autocomplete suggestions
     */
    public function autocomplete(string $query, int $limit = 8): array
    {
        if (strlen(trim($query)) < 2) {
            return ['suggestions' => []];
        }

        $searchResults = $this->search($query, [], $limit * 2, 1);
        $results = collect($searchResults['results']);

        // Group suggestions
        $products = $results->where('type', 'product')->take(ceil($limit * 0.6));
        $bundlings = $results->where('type', 'bundling')->take(floor($limit * 0.4));

        $suggestions = $products->merge($bundlings)
            ->sortByDesc('score')
            ->take($limit)
            ->values()
            ->toArray();

        return [
            'suggestions' => $suggestions,
            'query' => trim($query),
            'total' => count($suggestions)
        ];
    }

    /**
     * Get search suggestions for specific categories/brands
     */
    public function getPopularSuggestions(int $limit = 10): array
    {
        // Get popular products and bundlings
        $popularProducts = Product::query()
            ->with(['category:id,name,slug', 'brand:id,name,slug'])
            ->where('status', 'available')
            ->where('premiere', true) // Popular items
            ->orderBy('created_at', 'desc')
            ->take($limit / 2)
            ->get();

        $popularBundlings = Bundling::query()
            ->with(['category:id,name,slug', 'brand:id,name,slug'])
            ->where('status', 'available')
            ->orderBy('created_at', 'desc')
            ->take($limit / 2)
            ->get();

        $suggestions = [];

        foreach ($popularProducts as $product) {
            $suggestions[] = [
                'type' => 'product',
                'name' => $product->name,
                'slug' => $product->slug,
                'category' => $product->category?->name,
                'brand' => $product->brand?->name,
                'url' => "/product/{$product->slug}",
                'display' => $product->name,
            ];
        }

        foreach ($popularBundlings as $bundling) {
            $suggestions[] = [
                'type' => 'bundling',
                'name' => $bundling->name,
                'slug' => $bundling->slug,
                'category' => $bundling->category?->name,
                'brand' => $bundling->brand?->name,
                'url' => "/bundling/{$bundling->slug}",
                'display' => "📦 {$bundling->name}",
            ];
        }

        return ['suggestions' => $suggestions];
    }
}
