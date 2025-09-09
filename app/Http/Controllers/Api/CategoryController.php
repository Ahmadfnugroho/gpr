<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\CategoryResource;
use App\Models\Category;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        try {
            $categories = Category::withCount(['products', 'subCategories'])
                ->with('subCategories:id,name,slug,photo,category_id')
                ->get();
            return CategoryResource::collection($categories);
        } catch (\Exception $e) {
            \Log::error('Category index error: ' . $e->getMessage());
            return response()->json(['error' => 'Internal server error'], 500);
        }
    }
    public function show(Request $request, Category $category)
    {
        try {
            // Validate date parameters
            $request->validate([
                'start_date' => 'nullable|date|date_format:Y-m-d',
                'end_date' => 'nullable|date|date_format:Y-m-d|after_or_equal:start_date',
            ]);

            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');

            $category->loadCount(['products', 'subCategories']);
            
            // Load products with available_quantity calculation
            if ($startDate && $endDate) {
                $category->load([
                    'subCategories:id,name,slug,photo,category_id',
                    'products' => function ($query) use ($startDate, $endDate) {
                        $query->with([
                            'category:id,name,slug,photo',
                            'brand:id,name,slug,logo,premiere',
                            'subCategory:id,name,slug,photo,category_id',
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
                $category->load([
                    'subCategories:id,name,slug,photo,category_id',
                    'products' => function ($query) {
                        $query->with([
                            'category:id,name,slug,photo',
                            'brand:id,name,slug,logo,premiere',
                            'subCategory:id,name,slug,photo,category_id',
                            'rentalIncludes.includedProduct:id,name,slug,thumbnail',
                            'productSpecifications:id,product_id,name',
                            'productPhotos:id,product_id,photo',
                            'items:id,product_id,serial_number,is_available'
                        ])->withCount('items as available_quantity');
                    }
                ]);
            }
            
            return new CategoryResource($category);
        } catch (\Exception $e) {
            \Log::error('Category show error: ' . $e->getMessage());
            return response()->json(['error' => 'Internal server error'], 500);
        }
    }
}
