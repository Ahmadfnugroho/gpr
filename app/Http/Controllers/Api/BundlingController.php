<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\BundlingResource;
use App\Models\Bundling;
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
        $query = Bundling::query()->with([
            'bundlingPhotos',
            'products',
            'products.category',
            'products.brand',
            'products.subCategory',
            'products.productPhotos',
            'products.productSpecifications',
            'products.rentalIncludes.includedProduct'
        ]);

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
    public function show($slug)
    {
        $bundling = Bundling::with([
            'bundlingPhotos',
            'products',
            'products.category',
            'products.brand',
            'products.subCategory',
            'products.productPhotos',
            'products.productSpecifications',
            'products.rentalIncludes.includedProduct'
        ])
        ->where('slug', $slug)
        ->firstOrFail();
        
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
        $bundling->load(['bundlingPhotos', 'products']);
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
        $bundling->load(['bundlingPhotos', 'products']);
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
