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
            'quantity' => $this->quantity,
            'included_product' => $this->whenLoaded('includedProduct', function () {
                return $this->includedProduct ? [
                    'id' => $this->includedProduct->id,
                    'name' => $this->includedProduct->name,
                    'slug' => $this->includedProduct->slug,
                ] : null;
            }),
        ];
    }
}
