<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'event'      => $this->event,
            'old_values' => $this->old_values,
            'new_values' => $this->new_values,
            'actor'      => $this->whenLoaded('user', fn () => new UserResource($this->user)),
            'created_at' => $this->created_at?->toISOString(),
            // Security: ip_address is shown only to admins (authorization at policy level)
        ];
    }
}
