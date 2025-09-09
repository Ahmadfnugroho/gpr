<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     * DATABASE VALUES ONLY - NO CALCULATIONS OR OVERRIDES
     * Ensures API returns exactly what's stored in database
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            // Core transaction data - direct from database
            'id' => $this->id,
            'booking_transaction_id' => $this->booking_transaction_id,
            
            // Financial fields - DATABASE VALUES ONLY, no calculations
            'grand_total' => (int) ($this->grand_total ?? 0),
            'down_payment' => (int) ($this->down_payment ?? 0), // ← DATABASE ONLY
            'remaining_payment' => (int) ($this->remaining_payment ?? 0), // ← DATABASE ONLY
            'cancellation_fee' => (int) ($this->cancellation_fee ?? 0),
            
            // Status and dates - direct from database
            'booking_status' => $this->booking_status,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'duration' => (int) ($this->duration ?? 0),
            'note' => $this->note,
            
            // Additional services - direct from database
            'additional_services' => $this->additional_services,
            
            // Legacy additional fees - direct from database
            'additional_fee_1_name' => $this->additional_fee_1_name,
            'additional_fee_1_amount' => (int) ($this->additional_fee_1_amount ?? 0),
            'additional_fee_2_name' => $this->additional_fee_2_name,
            'additional_fee_2_amount' => (int) ($this->additional_fee_2_amount ?? 0),
            'additional_fee_3_name' => $this->additional_fee_3_name,
            'additional_fee_3_amount' => (int) ($this->additional_fee_3_amount ?? 0),
            
            // Timestamps
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            // Relationships - only when loaded
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
            
            // Meta information for API consumers
            '_meta' => [
                'data_source' => 'database_only',
                'no_calculations' => true,
                'note' => 'All financial values are returned exactly as stored in database'
            ]
        ];
    }
}
