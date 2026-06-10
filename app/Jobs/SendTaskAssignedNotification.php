<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Task;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendTaskAssignedNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 30;

    public function __construct(
        public readonly Task $task,
        public readonly User $assignedBy,
    ) {}

    public function handle(): void
    {
        $assignee = $this->task->assignee;

        if (! $assignee) {
            Log::warning('SendTaskAssignedNotification: assignee not found', ['task_id' => $this->task->id]);

            return;
        }

        // Security: only log task ID and user ID, never log task content or user PII
        Log::info('Sending task assignment notification', [
            'task_id'     => $this->task->id,
            'assignee_id' => $assignee->id,
        ]);

        // Mail::to($assignee->email)->send(new TaskAssignedMail($this->task, $this->assignedBy));
        // TODO: Implement TaskAssignedMail mailable
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SendTaskAssignedNotification failed', [
            'task_id' => $this->task->id,
            'error'   => $exception->getMessage(),
        ]);
    }
}
