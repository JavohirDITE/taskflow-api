<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\TaskStatus;
use App\Interfaces\TaskRepositoryInterface;
use App\Models\Task;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class TaskRepository implements TaskRepositoryInterface
{
    public function findById(int $id): ?Task
    {
        return Task::with(['project', 'creator', 'assignee'])->find($id);
    }

    public function findByIdOrFail(int $id): Task
    {
        return Task::with(['project', 'creator', 'assignee'])->findOrFail($id);
    }

    public function getByProject(int $projectId, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Task::with(['creator', 'assignee'])
            ->where('project_id', $projectId)
            ->active();

        if (isset($filters['status'])) {
            $query->byStatus(TaskStatus::from($filters['status']));
        }

        if (isset($filters['assignee_id'])) {
            $query->assignedTo((int) $filters['assignee_id']);
        }

        if (isset($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (isset($filters['overdue']) && $filters['overdue']) {
            $query->overdue();
        }

        $sortField = $filters['sort'] ?? 'created_at';
        $sortDir   = $filters['direction'] ?? 'desc';

        // Security: only allow sorting by safe column names
        $allowedSortFields = ['created_at', 'due_date', 'priority', 'status', 'title'];
        if (! in_array($sortField, $allowedSortFields, true)) {
            $sortField = 'created_at';
        }

        return $query->orderBy($sortField, $sortDir === 'asc' ? 'asc' : 'desc')
            ->paginate($perPage);
    }

    public function getByAssignee(int $userId, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return Task::with(['project', 'creator'])
            ->assignedTo($userId)
            ->active()
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function getOverdueTasks(): Collection
    {
        return Task::with(['project', 'assignee'])
            ->overdue()
            ->active()
            ->get();
    }

    public function create(array $data): Task
    {
        return Task::create($data);
    }

    public function update(Task $task, array $data): Task
    {
        $task->update($data);

        return $task->fresh(['project', 'creator', 'assignee']);
    }

    public function delete(Task $task): bool
    {
        return (bool) $task->delete();
    }

    public function updateStatus(Task $task, string $status): Task
    {
        $task->update(['status' => $status]);

        return $task->fresh();
    }
}
