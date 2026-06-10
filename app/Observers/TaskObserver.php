<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\Task;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * TaskObserver — автоматически логирует изменения задач через Model Events.
 * Этот подход дополняет ручное логирование в TaskService для дополнительной надёжности.
 */
class TaskObserver
{
    /**
     * Handle the Task "updating" event.
     * Logs field-level changes before they are saved.
     */
    public function updating(Task $task): void
    {
        // Skip if no dirty attributes
        if (! $task->isDirty()) {
            return;
        }

        $changed    = $task->getDirty();
        $original   = $task->getOriginal();

        // Build old/new value arrays only for changed fields
        $oldValues  = array_intersect_key($original, $changed);
        $newValues  = $changed;

        // Security: never log sensitive data
        $sensitiveKeys = ['password', 'remember_token'];
        foreach ($sensitiveKeys as $key) {
            unset($oldValues[$key], $newValues[$key]);
        }

        AuditLog::create([
            'task_id'    => $task->id,
            'user_id'    => Auth::id(),
            'event'      => 'updated',
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
        ]);
    }

    /**
     * Handle the Task "deleted" event.
     */
    public function deleted(Task $task): void
    {
        Log::info('Task soft-deleted', [
            'task_id' => $task->id,
            'user_id' => Auth::id(),
        ]);
    }
}
