<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttachmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'original_name' => $this->original_name,
            'size'          => $this->getSizeHumanReadable(),
            'size_bytes'    => $this->size_bytes,
            'mime_type'     => $this->mime_type,
            'download_url'  => route('api.v1.attachments.download', $this->id),
            'uploader'      => $this->whenLoaded('uploader', fn () => new UserResource($this->uploader)),
            'created_at'    => $this->created_at?->toISOString(),
            // Security: stored_name (UUID) and actual path are NEVER exposed in the API response
        ];
    }
}
