<?php

declare(strict_types=1);

use App\Enums\RoleType;
use App\Enums\UserStatus;
use App\Models\Platform\Role;
use App\Models\Platform\User;
use Illuminate\Support\Facades\Hash;

function jwtMiddlewareUser(
    string $email = 'jwt-middleware@example.com',
): User {
    return User::query()->create([
        'name' => 'JWT Middleware User',
        'email' => $email,
        'password' => Hash::make('correct-password'),
        'status' => UserStatus::ACTIVE,
    ]);
}

function jwtMiddlewarePlatformAdmin(
    string $email = 'jwt-platform-admin@example.com',
): User {
    $user = jwtMiddlewareUser($email);

    $role = Role::query()->firstOrCreate(
        [
            'slug' => 'platform_admin',
            'type' => RoleType::PLATFORM,
        ],
        [
            'name' => 'Platform Administrator',
            'is_system' => true,
        ],
    );

    $user->platformRoles()->attach($role);

    return $user;
}

function jwtMiddlewareLogin(
    string $email,
): string {
    return test()
        ->postJson(
            '/api/v1/auth/login',
            [
                'email' => $email,
                'password' => 'correct-password',
            ],
        )
        ->assertOk()
        ->json('data.access_token');
}

it('allows an authenticated jwt user to access auth me', function (): void {
    $user = jwtMiddlewareUser();

    $token = jwtMiddlewareLogin(
        $user->email,
    );

    $this
        ->withToken($token)
        ->getJson('/api/v1/auth/me')
        ->assertOk()
        ->assertJsonPath(
            'data.id',
            $user->id,
        );
});

it('rejects a request without a jwt', function (): void {
    $this
        ->getJson('/api/v1/auth/me')
        ->assertUnauthorized();
});

it('rejects an invalid jwt', function (): void {
    $this
        ->withToken('invalid.jwt.token')
        ->getJson('/api/v1/auth/me')
        ->assertUnauthorized();
});

it('requires jwt authentication for platform endpoints', function (): void {
    $this
        ->getJson('/api/v1/platform/tenants')
        ->assertUnauthorized();
});

it('allows a platform administrator with a valid jwt', function (): void {
    $user = jwtMiddlewarePlatformAdmin();

    $token = jwtMiddlewareLogin(
        $user->email,
    );

    $this
        ->withToken($token)
        ->getJson('/api/v1/platform/tenants')
        ->assertOk();
});

it('rejects an authenticated non-platform user from platform endpoints', function (): void {
    $user = jwtMiddlewareUser(
        'jwt-normal-user@example.com',
    );

    $token = jwtMiddlewareLogin(
        $user->email,
    );

    $this
        ->withToken($token)
        ->getJson('/api/v1/platform/tenants')
        ->assertForbidden();
});
