<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'price' => $this->price instanceof \App\Casts\MoneyCast
                ? (int)$this->price // Pastikan angka (bukan object)
                : $this->price,
            'thumbnail' => $this->thumbnail,
            'status' => $this->status,
            'slug' => $this->slug,
            'premiere' => (bool)$this->premiere,
            'is_available' => (bool)$this->is_available, // dari accessor

            // Category (nullable)
            'category' => $this->whenLoaded('category', function () {
                return $this->category ? [
                    'id' => $this->category->id,
                    'name' => $this->category->name,
                    'slug' => $this->category->slug,
                ] : null;
            }),

            // Brand (nullable)
            'brand' => $this->whenLoaded('brand', function () {
                return $this->brand ? [
                    'id' => $this->brand->id,
                    'name' => $this->brand->name,
                    'slug' => $this->brand->slug,
                ] : null;
            }),

            // SubCategory (nullable)
            'subCategory' => $this->whenLoaded('subCategory', function () {
                return $this->subCategory ? [
                    'id' => $this->subCategory->id,
                    'name' => $this->subCategory->name,
                    'slug' => $this->subCategory->slug,
                ] : null;
            }),

            // Product Photos
            'productPhotos' => $this->whenLoaded('productPhotos', function () {
                return $this->productPhotos->map(function ($photo) {
                    return [
                        'id' => $photo->id,
                        'photo' => $photo->photo,
                    ];
                });
            }),

            // Product Specifications
            'productSpecifications' => $this->whenLoaded(
                'productSpecifications',
                fn() =>
                $this->productSpecifications->map(fn($spec) => [
                    'id' => $spec->id,
                    'name' => $spec->name,
                    'name' => $spec->name,
                ])
            ),

            'rentalIncludes' => $this->whenLoaded(
                'rentalIncludes',
                fn() =>
                $this->rentalIncludes->map(fn($include) => [
                    'id' => $include->id,
                    'quantity' => $include->quantity,
                    'included_product' => $include->includedProduct ? [
                        'id' => $include->includedProduct->id,
                        'name' => $include->includedProduct->name,
                        'slug' => $include->includedProduct->slug,
                    ] : null,
                ])
            ),
        ];
    }
}
