<?php

declare(strict_types=1);

namespace App\Interfaces;

use App\Models\Task;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface TaskRepositoryInterface
{
    public function findById(int $id): ?Task;

    public function findByIdOrFail(int $id): Task;

    public function getByProject(int $projectId, array $filters = [], int $perPage = 20): LengthAwarePaginator;

    public function getByAssignee(int $userId, array $filters = [], int $perPage = 20): LengthAwarePaginator;

    public function getOverdueTasks(): Collection;

    public function create(array $data): Task;

    public function update(Task $task, array $data): Task;

    public function delete(Task $task): bool;

    public function updateStatus(Task $task, string $status): Task;
}
