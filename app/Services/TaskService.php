<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\SendTaskAssignedNotification;
use App\Models\Task;
use App\Models\User;
use App\Interfaces\TaskRepositoryInterface;
use App\Models\AuditLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TaskService
{
    public function __construct(
        private readonly TaskRepositoryInterface $taskRepository,
    ) {}

    public function getProjectTasks(int $projectId, array $filters, User $viewer): LengthAwarePaginator
    {
        return $this->taskRepository->getByProject($projectId, $filters);
    }

    public function createTask(array $data, User $creator): Task
    {
        return DB::transaction(function () use ($data, $creator) {
            $task = $this->taskRepository->create([
                ...$data,
                'creator_id' => $creator->id,
            ]);

            $this->logAudit($task, $creator, 'created', [], $task->toArray());

            // Dispatch notification if task is assigned to someone else
            if ($task->assignee_id && $task->assignee_id !== $creator->id) {
                SendTaskAssignedNotification::dispatch($task, $creator)
                    ->onQueue('notifications');
            }

            Log::info('Task created', ['task_id' => $task->id, 'creator_id' => $creator->id]);

            return $task;
        });
    }

    public function updateTask(Task $task, array $data, User $actor): Task
    {
        return DB::transaction(function () use ($task, $data, $actor) {
            $oldValues = $task->only(array_keys($data));

            $updatedTask = $this->taskRepository->update($task, $data);

            $this->logAudit($updatedTask, $actor, 'updated', $oldValues, $data);

            // Notify new assignee if changed
            if (
                isset($data['assignee_id'])
                && $data['assignee_id'] !== $task->getOriginal('assignee_id')
                && $data['assignee_id'] !== $actor->id
            ) {
                SendTaskAssignedNotification::dispatch($updatedTask, $actor)
                    ->onQueue('notifications');
            }

            return $updatedTask;
        });
    }

    public function updateTaskStatus(Task $task, string $status, User $actor): Task
    {
        $oldStatus = $task->status->value;

        $updatedTask = $this->taskRepository->updateStatus($task, $status);

        $this->logAudit($updatedTask, $actor, 'status_changed', ['status' => $oldStatus], ['status' => $status]);

        return $updatedTask;
    }

    public function deleteTask(Task $task, User $actor): void
    {
        DB::transaction(function () use ($task, $actor) {
            $this->logAudit($task, $actor, 'deleted', $task->toArray(), []);
            $this->taskRepository->delete($task);
        });
    }

    private function logAudit(Task $task, User $actor, string $event, array $oldValues, array $newValues): void
    {
        // Security: do not log sensitive fields
        $sensitiveFields = ['password', 'token', 'secret'];
        foreach ($sensitiveFields as $field) {
            unset($oldValues[$field], $newValues[$field]);
        }

        AuditLog::create([
            'task_id'    => $task->id,
            'user_id'    => $actor->id,
            'event'      => $event,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
        ]);
    }
}
