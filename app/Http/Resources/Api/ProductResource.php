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
            'quantity' => $this->whenLoaded('items', function () {
                return $this->items->count();
            }) ?: 0,
            'price' => $this->price,
            'thumbnail' => $this->thumbnail,
            'status' => $this->status,
            'description' => $this->description ?? '',
            'slug' => $this->slug,
            'premiere' => (int) $this->premiere, // Cast to int to match TypeScript number | boolean

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

            'subCategory' => $this->whenLoaded('subCategory', function () {
                return $this->subCategory ? [
                    'id' => $this->subCategory->id,
                    'name' => $this->subCategory->name,
                    'photo' => $this->subCategory->photo,
                    'slug' => $this->subCategory->slug,
                ] : null;
            }),

            'rentalIncludes' => $this->whenLoaded('rentalIncludes', function () {
                return $this->rentalIncludes->map(function ($rentalInclude) {
                    return [
                        'id' => $rentalInclude->id,
                        'quantity' => $rentalInclude->quantity,
                        'included_product' => $rentalInclude->includedProduct ? [
                            'id' => $rentalInclude->includedProduct->id,
                            'name' => $rentalInclude->includedProduct->name,
                            'slug' => $rentalInclude->includedProduct->slug,
                        ] : null,
                    ];
                });
            }),

            'productSpecifications' => $this->whenLoaded('productSpecifications', function () {
                return $this->productSpecifications->map(function ($spec) {
                    return [
                        'id' => $spec->id,
                        'product_id' => $spec->product_id,
                        'name' => $spec->name,
                    ];
                });
            }),

            'productPhotos' => $this->whenLoaded('productPhotos', function () {
                return $this->productPhotos->map(function ($photo) {
                    return [
                        'id' => $photo->id,
                        'photo' => $photo->photo,
                    ];
                });
            }),
        ];
    }
}
