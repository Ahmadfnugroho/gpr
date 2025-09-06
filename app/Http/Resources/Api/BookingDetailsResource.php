<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingDetailsResource extends JsonResource
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
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                    'phone' => $this->user->phoneNumbers ? $this->user->phoneNumbers->map(function ($phone) {
                        return [
                            'id' => $phone->id,
                            'phone_number' => $phone->phone_number,
                        ];
                    }) : [],
                ];
            }),
            'booking_transaction_id' => $this->booking_transaction_id,
            'grand_total' => $this->grand_total,
            'status' => $this->status,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'duration' => $this->duration,
            'product' => $this->whenLoaded('product', function () {
                return new ProductResource($this->product);
            }),
            'quantity' => $this->quantity,
            'include' => $this->whenLoaded('rentalInclude', function () {
                return new RentalIncludeResource($this->rentalInclude);
            }),
        ];
    }
}
