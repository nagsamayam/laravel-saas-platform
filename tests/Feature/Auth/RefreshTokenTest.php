<?php

declare(strict_types=1);

use App\Enums\UserStatus;
use App\Models\Platform\RefreshToken;
use App\Models\Platform\User;
use Illuminate\Support\Facades\Hash;

function refreshTokenUser(
    string $email = 'refresh@example.com',
): User {
    return User::query()->create([
        'name' => 'Refresh User',
        'email' => $email,
        'password' => Hash::make('correct-password'),
        'status' => UserStatus::ACTIVE,
    ]);
}

function loginForRefreshToken(
    string $email = 'refresh@example.com',
): string {
    return test()
        ->postJson(
            '/api/v1/auth/login',
            [
                'email' => $email,
                'password' => 'correct-password',
            ],
        )
        ->json('data.refresh_token');
}

it('returns a refresh token during login', function (): void {
    refreshTokenUser();

    $response = $this->postJson(
        '/api/v1/auth/login',
        [
            'email' => 'refresh@example.com',
            'password' => 'correct-password',
        ],
    );

    $response
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'access_token',
                'refresh_token',
                'expires_in',
                'refresh_expires_in',
            ],
        ]);
});

it('stores only a hash of the refresh token', function (): void {
    refreshTokenUser();

    $refreshToken = loginForRefreshToken();

    $stored = RefreshToken::query()
        ->latest('created_at')
        ->firstOrFail();

    expect($stored->token_hash)
        ->not->toBe($refreshToken);

    expect($stored->token_hash)
        ->toBe(hash('sha256', $refreshToken));
});

it('rotates a valid refresh token', function (): void {
    refreshTokenUser();

    $refreshToken = loginForRefreshToken();

    $response = $this->postJson(
        '/api/v1/auth/refresh',
        [
            'refresh_token' => $refreshToken,
        ],
    );

    $response
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'access_token',
                'refresh_token',
                'expires_in',
                'refresh_expires_in',
            ],
        ]);

    $newRefreshToken = $response->json(
        'data.refresh_token'
    );

    expect($newRefreshToken)
        ->not->toBe($refreshToken);
});

it('revokes the old refresh token after rotation', function (): void {
    refreshTokenUser();

    $refreshToken = loginForRefreshToken();

    $this->postJson(
        '/api/v1/auth/refresh',
        [
            'refresh_token' => $refreshToken,
        ],
    )->assertOk();

    $this
        ->postJson(
            '/api/v1/auth/refresh',
            [
                'refresh_token' => $refreshToken,
            ],
        )
        ->assertStatus(500);
});
