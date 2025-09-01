<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Bundling;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ProductSearchController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search', '');
        $startDate = $request->get('start_date', now()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->addDays(7)->format('Y-m-d'));

        $startDateTime = Carbon::parse($startDate)->startOfDay();
        $endDateTime = Carbon::parse($endDate)->endOfDay();

        $products = collect();

        // Search products
        $productQuery = Product::where('status', 'available')
            ->with(['items', 'detailTransactions.transaction']);

        if (!empty($search)) {
            $productQuery->where('name', 'like', '%' . $search . '%');
        }

        $allProducts = $productQuery->get();

        foreach ($allProducts as $product) {
            $availableCount = $this->calculateProductAvailability($product, $startDateTime, $endDateTime);
            
            $products->push([
                'id' => $product->id,
                'name' => $product->name,
                'type' => 'product',
                'price' => $product->price,
                'thumbnail' => $product->thumbnail,
                'available' => $availableCount > 0,
                'available_count' => $availableCount,
                'included_products' => [],
            ]);
        }

        // Search bundlings
        $bundlingQuery = Bundling::with(['products.items', 'products.detailTransactions.transaction']);

        if (!empty($search)) {
            $bundlingQuery->where('name', 'like', '%' . $search . '%');
        }

        $allBundlings = $bundlingQuery->get();

        foreach ($allBundlings as $bundling) {
            $availableCount = $this->calculateBundlingAvailability($bundling, $startDateTime, $endDateTime);
            $includedProducts = $bundling->products->pluck('name')->toArray();
            
            $products->push([
                'id' => $bundling->id,
                'name' => $bundling->name,
                'type' => 'bundling',
                'price' => $bundling->price,
                'thumbnail' => $bundling->thumbnail ?? null,
                'available' => $availableCount > 0,
                'available_count' => $availableCount,
                'included_products' => $includedProducts,
            ]);
        }

        // Sort by availability (available first) then by name
        $products = $products->sortBy([
            ['available', 'desc'],
            ['name', 'asc']
        ])->values();

        return view('product-search', compact('products', 'search', 'startDate', 'endDate'));
    }

    /**
     * Calculate product availability for given date range
     */
    private function calculateProductAvailability(Product $product, Carbon $startDate, Carbon $endDate): int
    {
        // Get total items for this product
        $totalItems = $product->items()->where('is_available', true)->count();

        // Get items that are already booked during this period
        $bookedItems = $product->detailTransactions()
            ->whereHas('transaction', function ($query) use ($startDate, $endDate) {
                $query->where('booking_status', '!=', 'cancel')
                    ->where(function ($q) use ($startDate, $endDate) {
                        // Check for overlapping date ranges
                        $q->whereBetween('start_date', [$startDate, $endDate])
                            ->orWhereBetween('end_date', [$startDate, $endDate])
                            ->orWhere(function ($q2) use ($startDate, $endDate) {
                                // Target range completely within transaction range
                                $q2->where('start_date', '<=', $startDate)
                                    ->where('end_date', '>=', $endDate);
                            });
                    });
            })
            ->with('productItems')
            ->get()
            ->flatMap(function ($detail) {
                return $detail->productItems->pluck('id');
            })
            ->unique()
            ->count();

        return max(0, $totalItems - $bookedItems);
    }

    /**
     * Calculate bundling availability for given date range
     */
    private function calculateBundlingAvailability(Bundling $bundling, Carbon $startDate, Carbon $endDate): int
    {
        if ($bundling->products->isEmpty()) {
            return 0;
        }

        $minAvailable = PHP_INT_MAX;

        foreach ($bundling->products as $product) {
            $requiredQuantity = $product->pivot->quantity ?? 1;
            $availableQuantity = $this->calculateProductAvailability($product, $startDate, $endDate);
            
            // Calculate how many complete sets we can make
            $possibleSets = floor($availableQuantity / $requiredQuantity);
            $minAvailable = min($minAvailable, $possibleSets);
        }

        return $minAvailable === PHP_INT_MAX ? 0 : $minAvailable;
    }
}
