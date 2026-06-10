<?php

declare(strict_types=1);

namespace App\Http\Requests\Task;

use App\Enums\Priority;
use App\Enums\TaskStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'title'            => ['sometimes', 'required', 'string', 'min:3', 'max:255'],
            'description'      => ['nullable', 'string', 'max:10000'],
            'assignee_id'      => ['nullable', 'integer', 'exists:users,id'],
            'status'           => ['sometimes', Rule::enum(TaskStatus::class)],
            'priority'         => ['sometimes', Rule::enum(Priority::class)],
            'due_date'         => ['nullable', 'date'],
            'estimated_hours'  => ['nullable', 'numeric', 'min:0.5', 'max:999'],
        ];
    }
}
