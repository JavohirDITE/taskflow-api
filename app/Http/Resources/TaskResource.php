<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'title'            => $this->title,
            'description'      => $this->description,
            'status'           => [
                'value' => $this->status->value,
                'label' => $this->status->label(),
            ],
            'priority'         => [
                'value' => $this->priority->value,
                'label' => $this->priority->label(),
            ],
            'is_overdue'       => $this->isOverdue(),
            'due_date'         => $this->due_date?->toISOString(),
            'estimated_hours'  => $this->estimated_hours,
            'project_id'       => $this->project_id,
            'project'          => $this->whenLoaded('project', fn () => [
                'id'   => $this->project->id,
                'name' => $this->project->name,
            ]),
            'creator'          => $this->whenLoaded('creator', fn () => new UserResource($this->creator)),
            'assignee'         => $this->whenLoaded('assignee', fn () => new UserResource($this->assignee)),
            'comments_count'   => $this->whenLoaded('comments', fn () => $this->comments->count()),
            'comments'         => CommentResource::collection($this->whenLoaded('comments')),
            'attachments_count' => $this->whenLoaded('attachments', fn () => $this->attachments->count()),
            'attachments'      => AttachmentResource::collection($this->whenLoaded('attachments')),
            'audit_logs'       => AuditLogResource::collection($this->whenLoaded('auditLogs')),
            'created_at'       => $this->created_at?->toISOString(),
            'updated_at'       => $this->updated_at?->toISOString(),
        ];
    }
}
