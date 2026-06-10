<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Auth Registration', function () {
    it('registers a new user successfully', function () {
        $response = $this->postJson('/api/v1/auth/register', [
            'name'                  => 'John Doe',
            'email'                 => 'john@example.com',
            'password'              => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'user'         => ['id', 'name', 'email'],
                'access_token',
                'token_type',
                'expires_at',
            ]);

        $this->assertDatabaseHas('users', ['email' => 'john@example.com']);
    });

    it('rejects registration with invalid email', function () {
        $this->postJson('/api/v1/auth/register', [
            'name'                  => 'Test',
            'email'                 => 'not-an-email',
            'password'              => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['email']);
    });

    it('rejects weak passwords', function () {
        $this->postJson('/api/v1/auth/register', [
            'name'                  => 'Test',
            'email'                 => 'test@example.com',
            'password'              => '123456',
            'password_confirmation' => '123456',
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['password']);
    });

    it('rejects duplicate email registration', function () {
        User::factory()->create(['email' => 'existing@example.com']);

        $this->postJson('/api/v1/auth/register', [
            'name'                  => 'Other User',
            'email'                 => 'existing@example.com',
            'password'              => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['email']);
    });

    it('does not expose password in response', function () {
        $response = $this->postJson('/api/v1/auth/register', [
            'name'                  => 'Jane Doe',
            'email'                 => 'jane@example.com',
            'password'              => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ]);

        $response->assertStatus(201);
        $this->assertArrayNotHasKey('password', $response->json('user'));
    });
});

describe('Auth Login', function () {
    it('logs in with correct credentials', function () {
        $user = User::factory()->create(['password' => bcrypt('SecurePass123!')]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => $user->email,
            'password' => 'SecurePass123!',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['access_token', 'token_type', 'user']);
    });

    it('rejects login with wrong password', function () {
        $user = User::factory()->create(['password' => bcrypt('CorrectPass123!')]);

        $this->postJson('/api/v1/auth/login', [
            'email'    => $user->email,
            'password' => 'WrongPassword!',
        ])->assertStatus(401);
    });

    it('rejects login for inactive user', function () {
        $user = User::factory()->inactive()->create(['password' => bcrypt('Pass123!Word')]);

        $this->postJson('/api/v1/auth/login', [
            'email'    => $user->email,
            'password' => 'Pass123!Word',
        ])->assertStatus(401);
    });

    it('does not reveal whether email exists on failed login', function () {
        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => 'nonexistent@example.com',
            'password' => 'SomePassword123!',
        ]);

        // Security: generic message, not "email not found"
        $response->assertStatus(401);
        $body = $response->json();
        $this->assertStringNotContainsStringIgnoringCase('email', $body['message'] ?? '');
    });
});

describe('Auth Logout', function () {
    it('logs out and revokes token', function () {
        $user  = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/auth/logout')
            ->assertOk();

        // Token should no longer work
        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/auth/me')
            ->assertStatus(401);
    });
});

describe('Auth Me', function () {
    it('returns authenticated user profile', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('email', $user->email)
            ->assertJsonMissingPath('password');
    });

    it('requires authentication', function () {
        $this->getJson('/api/v1/auth/me')->assertStatus(401);
    });
});
