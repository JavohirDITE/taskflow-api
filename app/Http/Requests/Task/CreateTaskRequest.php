<?php

declare(strict_types=1);

namespace App\Http\Requests\Task;

use App\Enums\Priority;
use App\Enums\TaskStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'title'            => ['required', 'string', 'min:3', 'max:255'],
            'description'      => ['nullable', 'string', 'max:10000'],
            'assignee_id'      => ['nullable', 'integer', 'exists:users,id'],
            'status'           => ['nullable', Rule::enum(TaskStatus::class)],
            'priority'         => ['nullable', Rule::enum(Priority::class)],
            'due_date'         => ['nullable', 'date', 'after:now'],
            'estimated_hours'  => ['nullable', 'numeric', 'min:0.5', 'max:999'],
        ];
    }
}
