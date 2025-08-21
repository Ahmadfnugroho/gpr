<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\BrandResource;
use App\Models\Brand;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    /**
     * Fast, filterable, sortable, searchable brand list for frontend (landing page, no login)
     * Query params: ?q=search&sort=name&order=asc|desc&premiere=1
     */
    public function index(Request $request)
    {
        $query = Brand::query()->withCount('products');

        // Search by name
        if ($search = $request->query('q')) {
            $query->where('name', 'like', "%$search%");
        }

        // Filter by premiere
        if ($request->has('premiere')) {
            $query->where('premiere', (bool) $request->query('premiere'));
        }

        // Sorting
        $sort = $request->query('sort', 'name');
        $order = $request->query('order', 'asc');
        if (in_array($sort, ['name']) && in_array($order, ['asc', 'desc'])) {
            $query->orderBy($sort, $order);
        }

        // Limit
        $limit = (int) $request->query('limit', 12);

        $brands = $query
            ->select(['id', 'name', 'slug', 'logo', 'premiere'])
            ->limit($limit)
            ->get()
            ->map(function ($b) {
                return [
                    'id' => $b->id,
                    'name' => $b->name,
                    'slug' => $b->slug,
                    'logo' => $b->logo ? url('storage/' . ltrim($b->logo, '/')) : null,

                    'premiere' => (bool) $b->premiere,
                    'products_count' => $b->products_count,
                ];
            });

        return response()->json(['data' => $brands]);
    }

    /**
     * Detail brand dengan semua produk & relasi produk
     */
    public function show(Brand $brand)
    {
        $brand->loadCount('products');

        $brand->load([
            'products.category',
            'products.brand',
            'products.subCategory',
            'products.rentalIncludes',
            'products.productSpecifications',
            'products.productPhotos',
        ]);

        return new BrandResource($brand);
    }

    /**
     * Brand premiere (flag frontend)
     */
    public function getPremiereBrands()
    {
        $brands = Brand::withCount('products')
            ->where('premiere', true)
            ->get();

        return BrandResource::collection($brands);
    }
}
