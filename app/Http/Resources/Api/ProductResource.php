<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'price' => $this->price,
            'thumbnail' => $this->thumbnail,
            'status' => $this->status,
            'premiere' => $this->premiere,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            
            // Relationships
            'category' => $this->whenLoaded('category', function () {
                return [
                    'id' => $this->category->id,
                    'name' => $this->category->name,
                    'slug' => $this->category->slug,
                ];
            }),
            
            'brand' => $this->whenLoaded('brand', function () {
                return [
                    'id' => $this->brand->id,
                    'name' => $this->brand->name,
                    'slug' => $this->brand->slug,
                    'logo' => $this->brand->logo,
                ];
            }),
            
            'sub_category' => $this->whenLoaded('subCategory', function () {
                return $this->subCategory ? [
                    'id' => $this->subCategory->id,
                    'name' => $this->subCategory->name,
                    'slug' => $this->subCategory->slug,
                ] : null;
            }),
            
            'rental_includes' => $this->whenLoaded('rentalIncludes', function () {
                return $this->rentalIncludes->map(function ($rentalInclude) {
                    return [
                        'id' => $rentalInclude->id,
                        'name' => $rentalInclude->name,
                        'included_product' => $this->whenLoaded('includedProduct', function () use ($rentalInclude) {
                            return $rentalInclude->includedProduct ? [
                                'id' => $rentalInclude->includedProduct->id,
                                'name' => $rentalInclude->includedProduct->name,
                                'slug' => $rentalInclude->includedProduct->slug,
                            ] : null;
                        }),
                    ];
                });
            }),
            
            'product_specifications' => $this->whenLoaded('productSpecifications', function () {
                return $this->productSpecifications->map(function ($spec) {
                    return [
                        'id' => $spec->id,
                        'name' => $spec->name,
                    ];
                });
            }),
            
            'product_photos' => $this->whenLoaded('productPhotos', function () {
                return $this->productPhotos->map(function ($photo) {
                    return [
                        'id' => $photo->id,
                        'filename' => $photo->filename,
                        'url' => $photo->url ?? asset('storage/' . $photo->filename),
                    ];
                });
            }),
            
            // Count relationships
            'items_count' => $this->whenCounted('items'),
            'available_items_count' => $this->when(
                $this->relationLoaded('items'),
                function () {
                    return $this->items->where('is_available', true)->count();
                }
            ),
        ];
    }
}
