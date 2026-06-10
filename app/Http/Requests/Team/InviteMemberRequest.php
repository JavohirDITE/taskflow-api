<?php

declare(strict_types=1);

namespace App\Http\Requests\Team;

use App\Enums\TeamRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InviteMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'role'    => ['required', Rule::enum(TeamRole::class)->except(TeamRole::OWNER)],
        ];
    }
}
