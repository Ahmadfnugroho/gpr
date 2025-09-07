<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
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
            'additional_fee_1_name' => $this->additional_fee_1_name,
            'additional_fee_1_amount' => $this->additional_fee_1_amount,
            'additional_fee_2_name' => $this->additional_fee_2_name,
            'additional_fee_2_amount' => $this->additional_fee_2_amount,
            'additional_fee_3_name' => $this->additional_fee_3_name,
            'additional_fee_3_amount' => $this->additional_fee_3_amount,
            'additional_services' => $this->additional_services,
            'cancellation_fee' => $this->cancellation_fee,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            // Relationships
            'customer' => $this->whenLoaded('customer', function () {
                return new CustomerResource($this->customer);
            }),
            
            'user' => $this->whenLoaded('user', function () {
                return $this->user ? new UserResource($this->user) : null;
            }),
            
            'promo' => $this->whenLoaded('promo', function () {
                return $this->promo ? [
                    'id' => $this->promo->id,
                    'name' => $this->promo->name,
                    'discount' => $this->promo->discount ?? 0,
                ] : null;
            }),
            
            'detailTransactions' => $this->whenLoaded('detailTransactions', function () {
                return DetailTransactionResource::collection($this->detailTransactions);
            }),
        ];
    }
}
