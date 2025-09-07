<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\CategoryResource;
use App\Models\Category;
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
    public function show(Category $category)
    {
        try {
            $category->loadCount(['products', 'subCategories']);
            
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
                    ]);
                }
            ]);
            
            return new CategoryResource($category);
        } catch (\Exception $e) {
            \Log::error('Category show error: ' . $e->getMessage());
            return response()->json(['error' => 'Internal server error'], 500);
        }
    }
}
