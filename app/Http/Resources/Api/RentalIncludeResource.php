<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RentalIncludeResource extends JsonResource
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
            'product_id' => $this->product_id,
            'include_product_id' => $this->include_product_id,
            'quantity' => $this->quantity,
            'product' => $this->whenLoaded('product', function () {
                return $this->product ? [
                    'id' => $this->product->id,
                    'name' => $this->product->name,
                    'slug' => $this->product->slug,
                ] : null;
            }),
            'included_product' => $this->whenLoaded('includedProduct', function () {
                return $this->includedProduct ? [
                    'id' => $this->includedProduct->id,
                    'name' => $this->includedProduct->name,
                    'slug' => $this->includedProduct->slug,
                    'thumbnail' => $this->includedProduct->thumbnail ? asset('storage/' . ltrim($this->includedProduct->thumbnail, '/')) : null,
                ] : null;
            }),
        ];
    }
}
