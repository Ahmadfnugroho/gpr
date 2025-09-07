<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubCategoryResource extends JsonResource
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
            'category' => $this->whenLoaded('category', function () {
                return [
                    'id' => $this->category->id,
                    'name' => $this->category->name,
                    'slug' => $this->category->slug,
                    'photo' => $this->category->photo,
                ];
            }),
            'products_count' => $this->products_count,
            'products' => ProductResource::collection($this->whenLoaded('products')),
        ];
    }
}
