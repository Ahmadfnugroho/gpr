<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BrandResource extends JsonResource
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
            'logo' => $this->logo,
            'logo_url' => $this->logo_url, // From Brand model accessor
            'slug' => $this->slug,
            'premiere' => (bool) $this->premiere,
            'products_count' => $this->when(
                $this->products_count !== null, 
                $this->products_count
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
        ];
    }
}
