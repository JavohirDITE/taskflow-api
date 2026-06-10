<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Public endpoint
    }

    public function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'min:2', 'max:100'],
            // Security: validate email format server-side
            'email'    => ['required', 'string', 'email:rfc,dns', 'max:255', 'unique:users,email'],
            // Security: enforce strong password policy
            'password' => [
                'required',
                'confirmed',
                Password::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->uncompromised(), // Checks against HaveIBeenPwned
            ],
            'timezone' => ['nullable', 'string', 'timezone:all'],
        ];
    }

    public function messages(): array
    {
        return [
            'password.uncompromised' => 'This password has appeared in a data leak. Please choose a different password.',
        ];
    }
}
