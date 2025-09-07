<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DetailTransactionResource extends JsonResource
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
            'transaction_id' => $this->transaction_id,
            'product_id' => $this->product_id,
            'bundling_id' => $this->bundling_id,
            'quantity' => $this->quantity,
            'available_quantity' => $this->available_quantity,
            'price' => $this->price,
            'total_price' => $this->total_price,
            
            // Handle bundling serial numbers
            'bundling_serial_numbers' => $this->when(
                $this->bundling_serial_numbers !== null,
                $this->bundling_serial_numbers
            ),
            
            // Relationships
            'transaction' => $this->whenLoaded('transaction', function () {
                return new TransactionResource($this->transaction);
            }),
            
            'product' => $this->whenLoaded('product', function () {
                return $this->product ? new ProductResource($this->product) : null;
            }),
            
            'bundling' => $this->whenLoaded('bundling', function () {
                return $this->bundling ? new BundlingResource($this->bundling) : null;
            }),
            
            'productItems' => $this->whenLoaded('productItems', function () {
                return $this->productItems ? $this->productItems->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'serial_number' => $item->serial_number,
                        'is_available' => $item->is_available,
                        'product_id' => $item->product_id,
                    ];
                }) : [];
            }),
        ];
    }
}
