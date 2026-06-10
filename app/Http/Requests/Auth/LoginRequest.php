<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Public endpoint
    }

    public function rules(): array
    {
        return [
            // Security: validate input format to prevent injection
            'email'    => ['required', 'string', 'email:rfc', 'max:255'],
            'password' => ['required', 'string', 'max:128'],
        ];
    }
}
