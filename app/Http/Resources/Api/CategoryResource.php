<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'photo' => $this->photo,
            'slug' => $this->slug,
            'products_count' => $this->when(
                $this->products_count !== null,
                $this->products_count
            ),
            'subcategories_count' => $this->when(
                isset($this->sub_categories_count),
                $this->sub_categories_count
            ),
            'products' => ProductResource::collection($this->whenLoaded('products')),
            'bundlings' => $this->when(
                request()->has('include_bundlings') || $this->relationLoaded('bundlings'),
                function () {
                    // Get bundlings using the method we defined in the model
                    $bundlings = $this->bundlings()->with(['bundlingPhotos', 'products.category', 'products.brand'])->get();
                    return BundlingResource::collection($bundlings);
                }
            ),
            'bundlings_count' => $this->when(
                request()->has('include_bundlings_count'),
                function () {
                    return $this->bundlings()->count();
                }
            ),
            'subCategories' => $this->whenLoaded('subCategories', function () {
                return $this->subCategories->map(function ($subCategory) {
                    return [
                        'id' => $subCategory->id,
                        'name' => $subCategory->name,
                        'photo' => $subCategory->photo,
                        'slug' => $subCategory->slug,
                        'products_count' => $subCategory->products_count ?? 0,
                    ];
                });
            }),
        ];
    }
}
