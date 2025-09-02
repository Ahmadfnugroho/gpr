<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerPhotoResource extends JsonResource
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
            'photo_type' => $this->photo_type,
            'photo' => $this->photo ? asset('storage/' . $this->photo) : null,
            'id_type' => $this->id_type,
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
