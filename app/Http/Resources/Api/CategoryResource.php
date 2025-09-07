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
                $this->subcategories_count !== null,
                $this->subcategories_count
            ),
            'products' => ProductResource::collection($this->whenLoaded('products')),
            'subCategories' => SubCategoryResource::collection($this->whenLoaded('subCategories')),
        ];
    }
}
