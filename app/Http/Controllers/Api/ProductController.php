<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Symfony\Component\CssSelector\Node\FunctionNode;

class ProductController extends Controller
{
    /**
     * Fast, filterable, sortable, searchable product list for frontend (landing page, no login)
     * Query params: ?q=search&sort=price|name&order=asc|desc&category=slug&brand=slug&premiere=1&exclude_rental_includes=true
     * 
     * @param exclude_rental_includes boolean - When true, excludes products that are used as rental includes in other products
     */
    public function index(Request $request)
    {
        $query = Product::query()
            ->with([
                'brand:id,name,slug',
                'category:id,name,slug',
                'subCategory:id,name,slug',
                'productPhotos:id,product_id,photo',
                'productSpecifications',
                'rentalIncludes.includedProduct:id,name,slug'
            ]);

        // 🔍 Search
        if ($search = $request->query('q')) {
            $query->where('name', 'like', "%$search%");
        }


        // Kategori (multi)
        if ($categories = $request->query('category')) {
            $categorySlugs = is_array($categories) ? $categories : [$categories];
            $query->whereHas('category', fn($q) => $q->whereIn('slug', $categorySlugs));
        }

        // Brand (multi)
        if ($brands = $request->query('brand')) {
            $brandSlugs = is_array($brands) ? $brands : [$brands];
            $query->whereHas('brand', fn($q) => $q->whereIn('slug', $brandSlugs));
        }

        // Subkategori (multi)
        if ($subcategories = $request->query('subcategory')) {
            $subSlugs = is_array($subcategories) ? $subcategories : [$subcategories];
            $query->whereHas('subCategory', fn($q) => $q->whereIn('slug', $subSlugs));
        }

        // Status (multi)
        if ($available = $request->query('available')) {
            $statuses = is_array($available) ? $available : [$available];
            $query->whereIn('status', $statuses);
        }

        // ⭐ Filter: Premiere (rekomendasi)
        if ($request->has('premiere')) {
            $query->where('premiere', (bool)$request->query('premiere'));
        }

        // 🚫 Filter: Exclude products that are rental includes
        if ($request->boolean('exclude_rental_includes', false)) {
            $query->whereNotIn('id', function ($subQuery) {
                $subQuery->select('include_product_id')
                    ->from('rental_includes')
                    ->whereNotNull('include_product_id');
            });
        }

        // 🔼 Sorting
        $sort = $request->query('sort');
        $order = $request->query('order', 'asc');

        if ($sort === 'recommended') {
            // Produk dengan premiere=1 di atas
            $query->orderBy('premiere', 'desc');
        } elseif ($sort === 'latest') {
            // Terbaru berdasarkan created_at
            $query->orderBy('created_at', 'desc');
        } elseif ($sort === 'availability') {
            // Tersedia dulu
            $query->orderByRaw("FIELD(status, 'available') DESC");
        } elseif (in_array($sort, ['name', 'price']) && in_array($order, ['asc', 'desc'])) {
            $query->orderBy($sort, $order);
        } else {
            // Default: urutkan berdasarkan nama
            $query->orderBy('name', 'asc');
        }

        // 📐 Pagination
        $limit = $request->query('limit', 10); // Default 10
        $page = $request->query('page', 1);    // Default page 1

        $products = $query->paginate($limit, ['*'], 'page', $page);

        // Pastikan response mengikuti struktur API yang konsisten
        return ProductResource::collection($products);
    }
    public function show(Product $product)
    {
        $product->load(
            'category',
            'brand',
            'subCategory',
            'rentalIncludes.includedProduct:id,name,slug',
            'productSpecifications:id,product_id,name',
            'productPhotos'
        );
        return new ProductResource($product);
    }
    public function all(Product $product)
    {
        $product->load(
            'category',
            'brand',
            'thumbnail',
            'subCategory',
            'rentalIncludes.includedProduct',
            'productSpecifications',
            'productPhotos'
        );
        return new ProductResource($product);
    }



    public function ProductsHome()
    {
        $products = Product::where('premiere', 1)
            ->with(
                'category',
                'brand',
                'productPhotos'
            )
            ->get();

        return ProductResource::collection($products);
    }

    public function searchSuggestions(Request $request)
    {
        $query = $request->query('q');
        if (!$query || strlen($query) < 2) {
            return response()->json(['suggestions' => []]);
        }

        $lowerQuery = strtolower($query);

        // Ambil saran dari produk, kategori, brand, subkategori
        $suggestions = [];

        // 🔹 Produk
        $products = Product::whereRaw('LOWER(name) LIKE ?', ["%{$lowerQuery}%"])
            ->limit(5)
            ->get(['id', 'name', 'slug', 'thumbnail']);

        foreach ($products as $product) {
            $suggestions[] = [
                'type' => 'product',
                'name' => $product->name,
                'slug' => $product->slug,
                'thumbnail' => $product->thumbnail,
                'url' => "/product/{$product->slug}",
                'display' => $product->name,
            ];
        }

        // 🔹 Kategori
        $categories = Product::whereHas('category', fn($q) => $q->whereRaw('LOWER(name) LIKE ?', ["%{$lowerQuery}%"]))
            ->with('category:id,name,slug')
            ->limit(3)
            ->get()
            ->pluck('category')
            ->unique('id');

        foreach ($categories as $cat) {
            $suggestions[] = [
                'type' => 'category',
                'name' => $cat->name,
                'slug' => $cat->slug,
                'url' => "/browse-product?category={$cat->slug}",
                'display' => "Kategori: {$cat->name}",
            ];
        }

        // 🔹 Brand
        $brands = Product::whereHas('brand', fn($q) => $q->whereRaw('LOWER(name) LIKE ?', ["%{$lowerQuery}%"]))
            ->with('brand:id,name,slug')
            ->limit(3)
            ->get()
            ->pluck('brand')
            ->unique('id');

        foreach ($brands as $brand) {
            $suggestions[] = [
                'type' => 'brand',
                'name' => $brand->name,
                'slug' => $brand->slug,
                'url' => "/browse-product?brand={$brand->slug}",
                'display' => "Brand: {$brand->name}",
            ];
        }

        // 🔹 Subkategori
        $subcategories = Product::whereHas('subCategory', fn($q) => $q->whereRaw('LOWER(name) LIKE ?', ["%{$lowerQuery}%"]))
            ->with('subCategory:id,name,slug')
            ->limit(3)
            ->get()
            ->pluck('subCategory')
            ->unique('id');

        foreach ($subcategories as $sub) {
            $suggestions[] = [
                'type' => 'subcategory',
                'name' => $sub->name,
                'slug' => $sub->slug,
                'url' => "/browse-product?subcategory={$sub->slug}",
                'display' => "Subkategori: {$sub->name}",
            ];
        }

        // Hilangkan duplikat berdasarkan `url`
        $unique = collect($suggestions)->unique('url')->values()->toArray();

        return response()->json([
            'suggestions' => array_slice($unique, 0, 10) // maks 10 saran
        ]);
    }
}
