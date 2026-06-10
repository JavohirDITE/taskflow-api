<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeamResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'slug'           => $this->slug,
            'description'    => $this->description,
            'is_active'      => $this->is_active,
            'owner'          => $this->whenLoaded('owner', fn () => new UserResource($this->owner)),
            'members'        => UserResource::collection($this->whenLoaded('members')),
            'members_count'  => $this->when(isset($this->members_count), $this->members_count),
            'projects_count' => $this->when(isset($this->projects_count), $this->projects_count),
            'projects'       => ProjectResource::collection($this->whenLoaded('projects')),
            'your_role'      => $this->when(
                $request->user()?->id,
                fn () => $request->user() ? $this->getMemberRole($request->user()->id)?->value : null
            ),
            'created_at'     => $this->created_at?->toISOString(),
        ];
    }
}
