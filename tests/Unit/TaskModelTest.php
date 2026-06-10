<?php

declare(strict_types=1);

use App\Enums\Priority;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Task Model', function () {
    it('casts status to TaskStatus enum', function () {
        $task = Task::factory()->make(['status' => 'todo']);
        expect($task->status)->toBeInstanceOf(TaskStatus::class);
        expect($task->status)->toBe(TaskStatus::TODO);
    });

    it('casts priority to Priority enum', function () {
        $task = Task::factory()->make(['priority' => 'urgent']);
        expect($task->priority)->toBeInstanceOf(Priority::class);
        expect($task->priority)->toBe(Priority::URGENT);
    });

    it('correctly detects overdue tasks', function () {
        $overdueTask = Task::factory()->overdue()->make();
        expect($overdueTask->isOverdue())->toBeTrue();
    });

    it('does not mark done tasks as overdue even with past due_date', function () {
        $task = Task::factory()->done()->make(['due_date' => now()->subDays(5)]);
        expect($task->isOverdue())->toBeFalse();
    });

    it('does not mark tasks without due_date as overdue', function () {
        $task = Task::factory()->todo()->make(['due_date' => null]);
        expect($task->isOverdue())->toBeFalse();
    });
});
