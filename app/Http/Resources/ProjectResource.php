<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'slug'        => $this->slug,
            'description' => $this->description,
            'color'       => $this->color,
            'is_active'   => $this->is_active,
            'team_id'     => $this->team_id,
            'tasks_count' => $this->when(isset($this->tasks_count), $this->tasks_count),
            'created_at'  => $this->created_at?->toISOString(),
        ];
    }
}
