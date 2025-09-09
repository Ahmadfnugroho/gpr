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
            'customer' => $this->whenLoaded('customer', function () {
                return $this->customer ? new CustomerResource($this->customer) : null;
            }),
            'user' => $this->whenLoaded('user', function () {
                return $this->user ? new UserResource($this->user) : null;
            }),
            'booking_transaction_id' => $this->booking_transaction_id,
            'grand_total' => $this->grand_total,
            'booking_status' => $this->booking_status,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'duration' => $this->duration,
            'note' => $this->note,
            'down_payment' => (int) ($this->down_payment ?? 0), // DATABASE ONLY
            'remaining_payment' => (int) ($this->remaining_payment ?? 0), // DATABASE ONLY
            'detailTransactions' => $this->whenLoaded('detailTransactions', function () {
                return DetailTransactionResource::collection($this->detailTransactions);
            }),
        ];
    }
}
