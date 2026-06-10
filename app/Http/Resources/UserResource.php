<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     * Security: explicitly define which fields to expose, never use $this->resource->toArray()
     */
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'email'      => $this->email,
            'timezone'   => $this->timezone,
            'is_active'  => $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
            // Security: password hash and remember_token are NEVER included
        ];
    }
}
