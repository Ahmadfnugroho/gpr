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
        $categories = Category::withCount(['products', 'subCategories'])
            ->with('subCategories:id,name,slug,category_id')
            ->get();
        return CategoryResource::collection($categories);
    }
    public function show(Category $category)
    {
        $category->load([
            'subCategories',
            'products.category',
            'products.brand',
            'products.subCategory',
            'products.rentalIncludes.includedProduct',
            'products.productSpecifications',
            'products.productPhotos',
        ]);
        $category->loadCount(['products', 'subCategories']);
        return new CategoryResource($category);
    }
}
