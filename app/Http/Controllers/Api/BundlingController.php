<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\BundlingResource;
use App\Models\Bundling;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BundlingController extends Controller
{
    // List all bundlings with products (RESTful, front-end friendly)
    /**
     * Fast, filterable, sortable, searchable bundling list for frontend (landing page, no login)
     * Query params: ?q=search&sort=price|name&order=asc|desc&premiere=1
     */
    public function index(Request $request)
    {
        // Validate date parameters
        $request->validate([
            'start_date' => 'nullable|date|date_format:Y-m-d',
            'end_date' => 'nullable|date|date_format:Y-m-d|after_or_equal:start_date',
        ]);

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $query = Bundling::query()->with([
            'bundlingPhotos',
            'products:id,name,slug,thumbnail,status,price,category_id,brand_id,sub_category_id',
            'products.category:id,name,slug',
            'products.brand:id,name,slug',
            'products.subCategory:id,name,slug',
            'products.productPhotos:id,product_id,photo',
            'products.productSpecifications:id,product_id,name',
            'products.rentalIncludes:id,product_id,include_product_id,quantity',
            'products.rentalIncludes.includedProduct:id,name,slug',
            'products.items:id,product_id,serial_number,is_available'
        ]);

        // Add available_quantity calculation for products in bundlings
        if ($startDate && $endDate) {
            $query->with([
                'products' => function ($q) use ($startDate, $endDate) {
                    $q->withCount([
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
            $query->with([
                'products' => function ($q) {
                    $q->withCount('items as available_quantity');
                }
            ]);
        }

        // Search by name
        if ($search = $request->query('q')) {
            $query->where('name', 'like', "%$search%");
        }

        // Filter by premiere
        if ($request->has('premiere')) {
            $query->where('premiere', (bool)$request->query('premiere'));
        }

        // Sorting
        $sort = $request->query('sort', 'name');
        $order = $request->query('order', 'asc');
        if (in_array($sort, ['name', 'price']) && in_array($order, ['asc', 'desc'])) {
            $query->orderBy($sort, $order);
        }

        // Pagination
        $limit = $request->query('limit', 12);
        $page = $request->query('page', 1);

        $bundlings = $query->paginate($limit, ['*'], 'page', $page);

        return BundlingResource::collection($bundlings);
    }

    // Show a single bundling by slug
    public function show(Request $request, $slug)
    {
        // Validate date parameters
        $request->validate([
            'start_date' => 'nullable|date|date_format:Y-m-d',
            'end_date' => 'nullable|date|date_format:Y-m-d|after_or_equal:start_date',
        ]);

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $query = Bundling::query()->with([
            'bundlingPhotos',
            'products:id,name,slug,thumbnail,status,price,category_id,brand_id,sub_category_id',
            'products.category:id,name,slug',
            'products.brand:id,name,slug',
            'products.subCategory:id,name,slug',
            'products.productPhotos:id,product_id,photo',
            'products.productSpecifications:id,product_id,name',
            'products.rentalIncludes:id,product_id,include_product_id,quantity',
            'products.rentalIncludes.includedProduct:id,name,slug',
            'products.items:id,product_id,serial_number,is_available'
        ]);

        // Add available_quantity calculation for products in bundling
        if ($startDate && $endDate) {
            $query->with([
                'products' => function ($q) use ($startDate, $endDate) {
                    $q->withCount([
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
            $query->with([
                'products' => function ($q) {
                    $q->withCount('items as available_quantity');
                }
            ]);
        }

        $bundling = $query->where('slug', $slug)->firstOrFail();
        
        return new BundlingResource($bundling);
    }

    // Create a new bundling
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'price' => 'required|numeric',
            'premiere' => 'nullable|boolean',
            'products' => 'required|array',
            'products.*.id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $bundling = Bundling::create($request->only(['name', 'price', 'premiere']));
        // Attach products with quantity
        foreach ($request->products as $product) {
            $bundling->products()->attach($product['id'], ['quantity' => $product['quantity']]);
        }
        $bundling->load([
            'bundlingPhotos',
            'products:id,name,slug,thumbnail,status,price'
        ]);
        return new BundlingResource($bundling);
    }

    // Update a bundling
    public function update(Request $request, $slug)
    {
        $bundling = Bundling::where('slug', $slug)->firstOrFail();
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'price' => 'sometimes|required|numeric',
            'premiere' => 'nullable|boolean',
            'products' => 'sometimes|array',
            'products.*.id' => 'required_with:products|exists:products,id',
            'products.*.quantity' => 'required_with:products|integer|min:1',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $bundling->update($request->only(['name', 'price', 'premiere']));
        if ($request->has('products')) {
            $syncData = [];
            foreach ($request->products as $product) {
                $syncData[$product['id']] = ['quantity' => $product['quantity']];
            }
            $bundling->products()->sync($syncData);
        }
        $bundling->load([
            'bundlingPhotos',
            'products:id,name,slug,thumbnail,status,price'
        ]);
        return new BundlingResource($bundling);
    }

    // Delete a bundling
    public function destroy($slug)
    {
        $bundling = Bundling::where('slug', $slug)->firstOrFail();
        $bundling->products()->detach();
        $bundling->delete();
        return response()->json(['message' => 'Bundling deleted successfully']);
    }
}
