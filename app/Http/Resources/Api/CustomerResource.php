<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
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
            'email' => $this->email,
            'email_verified_at' => $this->email_verified_at,
            'address' => $this->address,
            'job' => $this->job,
            'office_address' => $this->office_address,
            'instagram_username' => $this->instagram_username,
            'facebook_username' => $this->facebook_username,
            'emergency_contact_name' => $this->emergency_contact_name,
            'emergency_contact_number' => $this->emergency_contact_number,
            'gender' => $this->gender,
            'source_info' => $this->source_info,
            'status' => $this->status,
            'phone_number' => $this->phone_number, // Accessor from Customer model
            'phone_numbers' => CustomerPhoneNumberResource::collection($this->whenLoaded('customerPhoneNumbers')),
            'photos' => CustomerPhotoResource::collection($this->whenLoaded('customerPhotos')),
            'transactions' => $this->when($request->includes_transactions ?? false, function () {
                return TransactionResource::collection($this->transactions);
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
