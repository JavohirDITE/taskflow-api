<?php

declare(strict_types=1);

namespace App\Http\Requests\Team;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTeamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name'        => ['sometimes', 'required', 'string', 'min:2', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
