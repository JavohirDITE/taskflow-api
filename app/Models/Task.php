<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Priority;
use App\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'project_id',
        'creator_id',
        'assignee_id',
        'status',
        'priority',
        'due_date',
        'estimated_hours',
        'is_archived',
    ];

    protected function casts(): array
    {
        return [
            'status'           => TaskStatus::class,
            'priority'         => Priority::class,
            'due_date'         => 'datetime',
            'is_archived'      => 'boolean',
            'estimated_hours'  => 'decimal:2',
        ];
    }

    // =================== Relationships ===================

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    // =================== Scopes ===================

    public function scopeByStatus($query, TaskStatus $status): mixed
    {
        return $query->where('status', $status->value);
    }

    public function scopeByPriority($query, Priority $priority): mixed
    {
        return $query->where('priority', $priority->value);
    }

    public function scopeAssignedTo($query, int $userId): mixed
    {
        return $query->where('assignee_id', $userId);
    }

    public function scopeOverdue($query): mixed
    {
        return $query->where('due_date', '<', now())
            ->whereNotIn('status', [TaskStatus::DONE->value, TaskStatus::CANCELLED->value]);
    }

    public function scopeActive($query): mixed
    {
        return $query->where('is_archived', false);
    }

    // =================== Helpers ===================

    public function isOverdue(): bool
    {
        return $this->due_date
            && $this->due_date->isPast()
            && ! $this->status->isTerminal();
    }

    public function canBeEditedBy(User $user): bool
    {
        // Creator or assignee can edit, team admins can always edit (checked at controller level)
        return $this->creator_id === $user->id
            || $this->assignee_id === $user->id;
    }
}
