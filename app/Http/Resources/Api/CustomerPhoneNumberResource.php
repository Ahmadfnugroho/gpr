<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerPhoneNumberResource extends JsonResource
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
            'phone_number' => $this->phone_number,
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
