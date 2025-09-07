<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CheckTransactionResource extends JsonResource
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
            'booking_transaction_id' => $this->booking_transaction_id,
            'grand_total' => $this->grand_total,
            'booking_status' => $this->booking_status,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'duration' => $this->duration,
            'note' => $this->note,
            'down_payment' => $this->down_payment,
            'remaining_payment' => $this->remaining_payment,
            
            // Customer relationship (primary)
            'customer' => $this->whenLoaded('customer', function () {
                return $this->customer ? new CustomerResource($this->customer) : null;
            }),
            
            // Legacy user relationship (for backward compatibility)
            'user' => $this->whenLoaded('user', function () {
                return $this->user ? new UserResource($this->user) : null;
            }),
            
            // Direct customer info for quick access
            'customer_name' => $this->when($this->customer, $this->customer?->name),
            'customer_email' => $this->when($this->customer, $this->customer?->email),
            'customer_phone' => $this->when($this->customer, $this->customer?->phone_number),
            
            // Legacy fields for backward compatibility
            'user_name' => $this->when($this->user, $this->user?->name),
            'user_email' => $this->when($this->user, $this->user?->email),
            
            // Relationships
            'detailTransactions' => $this->whenLoaded('detailTransactions', function () {
                return DetailTransactionResource::collection($this->detailTransactions);
            }),
        ];
    }
}
