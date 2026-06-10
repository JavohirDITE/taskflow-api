<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Log;

class AuthService
{
    // TODO(security): Consider adding OAuth 2.0 provider integration (Google, GitHub)
    // TODO(security): Implement MFA (TOTP) for additional account security
    // TODO(security): Add leaked password detection via HaveIBeenPwned API

    /**
     * Register a new user.
     * Security: password is hashed by Laravel's bcrypt/argon2 via model cast.
     */
    public function register(array $data): array
    {
        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => $data['password'], // Hashed automatically via 'hashed' cast
            'timezone' => $data['timezone'] ?? 'UTC',
        ]);

        // Security: token has expiration set via SANCTUM_TOKEN_EXPIRATION env variable
        $token = $user->createToken(
            'auth_token',
            ['*'],
            now()->addMinutes((int) config('sanctum.expiration', 60))
        );

        Log::info('User registered', ['user_id' => $user->id]);

        return [
            'user'         => $user,
            'access_token' => $token->plainTextToken,
            'token_type'   => 'Bearer',
            'expires_at'   => $token->accessToken->expires_at,
        ];
    }

    /**
     * Authenticate user and return token.
     * Security: never log the password, even on failed attempts.
     */
    public function login(string $email, string $password): array
    {
        $user = User::where('email', $email)->first();

        // Security: use constant-time comparison via Hash::check to prevent timing attacks
        if (! $user || ! Hash::check($password, $user->password)) {
            // Security: generic error message, don't reveal if email exists
            throw new AuthenticationException('Invalid credentials.');
        }

        if (! $user->is_active) {
            throw new AuthenticationException('Account is deactivated.');
        }

        // Revoke all old tokens on new login (single session policy)
        // Comment out if you need multi-device support
        // $user->tokens()->delete();

        $token = $user->createToken(
            'auth_token',
            ['*'],
            now()->addMinutes((int) config('sanctum.expiration', 60))
        );

        Log::info('User logged in', ['user_id' => $user->id]);

        return [
            'user'         => $user,
            'access_token' => $token->plainTextToken,
            'token_type'   => 'Bearer',
            'expires_at'   => $token->accessToken->expires_at,
        ];
    }

    /**
     * Revoke the current token (logout).
     * Security: invalidates the specific token, not all tokens.
     */
    public function logout(User $user): void
    {
        // Delete only the current token used for this request
        $user->currentAccessToken()->delete();

        Log::info('User logged out', ['user_id' => $user->id]);
    }

    /**
     * Change user password and revoke all existing tokens.
     * Security: invalidates all sessions after password change.
     */
    public function changePassword(User $user, string $currentPassword, string $newPassword): void
    {
        if (! Hash::check($currentPassword, $user->password)) {
            throw new \InvalidArgumentException('Current password is incorrect.');
        }

        $user->update(['password' => $newPassword]); // Hashed via cast

        // Security: invalidate all sessions after password change
        $user->tokens()->delete();

        Log::info('User password changed', ['user_id' => $user->id]);
    }
}
