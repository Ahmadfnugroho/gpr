<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\BrandResource;
use App\Models\Brand;
use Carbon\Carbon;
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
            ->get();

        return BrandResource::collection($brands);
    }

    /**
     * Detail brand dengan semua produk & relasi produk
     */
    public function show(Request $request, Brand $brand)
    {
        // Validate date parameters
        $request->validate([
            'start_date' => 'nullable|date|date_format:Y-m-d',
            'end_date' => 'nullable|date|date_format:Y-m-d|after_or_equal:start_date',
        ]);

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $brand->loadCount('products');

        // Load products with available_quantity calculation
        if ($startDate && $endDate) {
            $brand->load([
                'products' => function ($query) use ($startDate, $endDate) {
                    $query->with([
                        'category:id,name,slug',
                        'brand:id,name,slug,logo',
                        'subCategory:id,name,slug,category_id',
                        'rentalIncludes.includedProduct:id,name,slug,thumbnail',
                        'productSpecifications:id,product_id,name',
                        'productPhotos:id,product_id,photo',
                        'items:id,product_id,serial_number,is_available'
                    ])->withCount([
                        'items as available_quantity' => function ($itemQuery) use ($startDate, $endDate) {
                            $itemQuery->whereDoesntHave('detailTransactions', function ($dtq) use ($startDate, $endDate) {
                                $dtq->whereHas('transaction', function ($tq) use ($startDate, $endDate) {
                                    $tq->whereIn('booking_status', ['booking', 'paid', 'on_rented'])
                                        ->where(function ($dateQuery) use ($startDate, $endDate) {
                                            $dateQuery
                                                ->whereBetween('start_date', [$startDate, $endDate])
                                                ->orWhereBetween('end_date', [$startDate, $endDate])
                                                ->orWhere(function ($encompassQuery) use ($startDate, $endDate) {
                                                    $encompassQuery->where('start_date', '<=', $startDate)
                                                        ->where('end_date', '>=', $endDate);
                                                });
                                        });
                                });
                            });
                        }
                    ]);
                }
            ]);
        } else {
            $brand->load([
                'products' => function ($query) {
                    $query->with([
                        'category:id,name,slug',
                        'brand:id,name,slug,logo',
                        'subCategory:id,name,slug,category_id',
                        'rentalIncludes.includedProduct:id,name,slug,thumbnail',
                        'productSpecifications:id,product_id,name',
                        'productPhotos:id,product_id,photo',
                        'items:id,product_id,serial_number,is_available'
                    ])->withCount('items as available_quantity');
                }
            ]);
        }

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
