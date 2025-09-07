<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BundlingPhotoResource extends JsonResource
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
            'photo' => $this->photo,
            'photo_url' => $this->photo ? asset('storage/' . ltrim($this->photo, '/')) : null,
            'bundling_id' => $this->bundling_id,
        ];
    }
}
