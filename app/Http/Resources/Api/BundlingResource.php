<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

class BundlingResource extends JsonResource
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
            'slug' => $this->slug,
            'premiere' => (bool)$this->premiere,
            
            // Products dalam bundling dengan rental includes
            'products' => $this->whenLoaded('products', function () {
                return $this->products->map(function ($product) {
                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'slug' => $product->slug,
                        'thumbnail' => $product->thumbnail,
                        'status' => $product->status,
                        'price' => $product->price instanceof \App\Casts\MoneyCast
                            ? (int)$product->price
                            : $product->price,
                        'quantity' => $product->pivot->quantity ?? 1,
                        
                        // Category
                        'category' => $product->category ? [
                            'id' => $product->category->id,
                            'name' => $product->category->name,
                            'slug' => $product->category->slug,
                        ] : null,
                        
                        // Brand
                        'brand' => $product->brand ? [
                            'id' => $product->brand->id,
                            'name' => $product->brand->name,
                            'slug' => $product->brand->slug,
                        ] : null,
                        
                        // SubCategory
                        'subCategory' => $product->subCategory ? [
                            'id' => $product->subCategory->id,
                            'name' => $product->subCategory->name,
                            'slug' => $product->subCategory->slug,
                        ] : null,
                        
                        // Product Photos
                        'productPhotos' => $product->productPhotos ? 
                            $product->productPhotos->map(function ($photo) {
                                return [
                                    'id' => $photo->id,
                                    'photo' => $photo->photo,
                                ];
                            }) : [],
                        
                        // Product Specifications
                        'productSpecifications' => $product->productSpecifications ?
                            $product->productSpecifications->map(function ($spec) {
                                return [
                                    'id' => $spec->id,
                                    'name' => $spec->name,
                                ];
                            }) : [],
                        
                        // Rental Includes
                        'rentalIncludes' => $product->rentalIncludes ?
                            $product->rentalIncludes->map(function ($include) {
                                return [
                                    'id' => $include->id,
                                    'quantity' => $include->quantity,
                                    'included_product' => $include->includedProduct ? [
                                        'id' => $include->includedProduct->id,
                                        'name' => $include->includedProduct->name,
                                        'slug' => $include->includedProduct->slug,
                                    ] : null,
                                ];
                            }) : [],
                    ];
                });
            }),
        ];
    }
}
