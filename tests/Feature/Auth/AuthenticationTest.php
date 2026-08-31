<?php

declare(strict_types=1);

use App\Enums\UserStatus;
use App\Models\Platform\User;
use Illuminate\Support\Facades\Hash;

function authenticationUser(
    string $email = 'auth@example.com',
): User {
    return User::query()->create([
        'name' => 'Authentication User',
        'email' => $email,
        'password' => Hash::make('correct-password'),
        'status' => UserStatus::ACTIVE,
    ]);
}

it('authenticates a user with valid credentials', function (): void {
    authenticationUser();

    $response = $this->postJson(
        '/api/v1/auth/login',
        [
            'email' => 'auth@example.com',
            'password' => 'correct-password',
        ],
    );

    $response
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'access_token',
                'token_type',
                'expires_in',
            ],
        ])
        ->assertJsonPath(
            'data.token_type',
            'Bearer',
        );
});

it('rejects invalid credentials', function (): void {
    authenticationUser();

    $this
        ->postJson(
            '/api/v1/auth/login',
            [
                'email' => 'auth@example.com',
                'password' => 'wrong-password',
            ],
        )
        ->assertUnauthorized()
        ->assertJson([
            'message' => 'Invalid credentials.',
        ]);
});

it('rejects login for a missing user', function (): void {
    $this
        ->postJson(
            '/api/v1/auth/login',
            [
                'email' => 'missing@example.com',
                'password' => 'password',
            ],
        )
        ->assertUnauthorized();
});

it('returns the authenticated user', function (): void {
    $user = authenticationUser();

    $login = $this->postJson(
        '/api/v1/auth/login',
        [
            'email' => 'auth@example.com',
            'password' => 'correct-password',
        ],
    );

    $token = $login->json(
        'data.access_token'
    );

    $this
        ->withHeader(
            'Authorization',
            "Bearer {$token}",
        )
        ->getJson('/api/v1/auth/me')
        ->assertOk()
        ->assertJsonPath(
            'data.id',
            $user->id,
        )
        ->assertJsonPath(
            'data.email',
            $user->email,
        );
});

it('logs out an authenticated user', function (): void {
    authenticationUser();

    $login = $this->postJson(
        '/api/v1/auth/login',
        [
            'email' => 'auth@example.com',
            'password' => 'correct-password',
        ],
    );

    $token = $login->json(
        'data.access_token'
    );

    $this
        ->withHeader(
            'Authorization',
            "Bearer {$token}",
        )
        ->postJson('/api/v1/auth/logout')
        ->assertOk()
        ->assertJson([
            'message' => 'Successfully logged out.',
        ]);
});

it('does not expose the password from the user resource', function (): void {
    authenticationUser();

    $login = $this->postJson(
        '/api/v1/auth/login',
        [
            'email' => 'auth@example.com',
            'password' => 'correct-password',
        ],
    );

    $token = $login->json(
        'data.access_token'
    );

    $this
        ->withHeader(
            'Authorization',
            "Bearer {$token}",
        )
        ->getJson('/api/v1/auth/me')
        ->assertOk()
        ->assertJsonMissingPath(
            'data.password'
        );
});

it('rejects an unauthenticated me request', function (): void {
    $this
        ->getJson('/api/v1/auth/me')
        ->assertUnauthorized();
});
