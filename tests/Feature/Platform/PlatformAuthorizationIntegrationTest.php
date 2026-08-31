<?php

declare(strict_types=1);

use App\Enums\RoleType;
use App\Enums\TenantStatus;
use App\Enums\UserStatus;
use App\Models\Platform\Role;
use App\Models\Platform\Tenant;
use App\Models\Platform\User;
use Illuminate\Support\Facades\Hash;

function authorizationIntegrationUser(
    string $email,
): User {
    return User::query()->create([
        'name' => 'Authorization User',
        'email' => $email,
        'password' => Hash::make('password'),
        'status' => UserStatus::ACTIVE,
    ]);
}

function authorizationIntegrationAdmin(
    string $email,
): User {
    $user = authorizationIntegrationUser($email);

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

    $user->platformRoles()->syncWithoutDetaching([
        $role->id,
    ]);

    return $user;
}

function authorizationIntegrationTenant(
    string $slug,
): Tenant {
    return Tenant::query()->create([
        'name' => 'Authorization Tenant',
        'slug' => $slug,
        'status' => TenantStatus::PENDING,
    ]);
}

it('allows a platform administrator to create a tenant', function (): void {
    $user = authorizationIntegrationAdmin(
        'authorization-create@example.com',
    );

    $token = loginAsUser($user);

    $this
        ->withToken($token)
        ->postJson('/api/v1/platform/tenants', [
            'name' => 'Authorized Tenant',
            'slug' => 'authorized-tenant',
        ])
        ->assertCreated();
});

it('rejects a non-platform user from creating a tenant', function (): void {
    $user = authorizationIntegrationUser(
        'authorization-create-denied@example.com',
    );

    $token = loginAsUser($user);

    $this
        ->withToken($token)
        ->postJson('/api/v1/platform/tenants', [
            'name' => 'Unauthorized Tenant',
            'slug' => 'unauthorized-tenant',
        ])
        ->assertForbidden();
});

it('allows a platform administrator to approve a tenant', function (): void {
    $user = authorizationIntegrationAdmin(
        'authorization-approve@example.com',
    );

    $tenant = authorizationIntegrationTenant(
        'authorization-approve',
    );

    $token = loginAsUser($user);

    $this
        ->withToken($token)
        ->postJson(
            "/api/v1/platform/tenants/{$tenant->id}/approve",
        )
        ->assertOk()
        ->assertJsonPath(
            'data.status',
            TenantStatus::APPROVED->value,
        );
});

it('rejects a non-platform user from approving a tenant', function (): void {
    $admin = authorizationIntegrationAdmin(
        'authorization-approve-owner@example.com',
    );

    $user = authorizationIntegrationUser(
        'authorization-approve-denied@example.com',
    );

    $tenant = authorizationIntegrationTenant(
        'authorization-approve-denied',
    );

    $token = loginAsUser($user);

    $this
        ->withToken($token)
        ->postJson(
            "/api/v1/platform/tenants/{$tenant->id}/approve",
        )
        ->assertForbidden();

    expect($tenant->fresh()->status)
        ->toBe(TenantStatus::PENDING);
});

it('allows a platform administrator to view a tenant', function (): void {
    $user = authorizationIntegrationAdmin(
        'authorization-view@example.com',
    );

    $tenant = authorizationIntegrationTenant(
        'authorization-view',
    );

    $token = loginAsUser($user);

    $this
        ->withToken($token)
        ->getJson(
            "/api/v1/platform/tenants/{$tenant->id}",
        )
        ->assertOk();
});

it('rejects a non-platform user from viewing a tenant', function (): void {
    $admin = authorizationIntegrationAdmin(
        'authorization-view-owner@example.com',
    );

    $user = authorizationIntegrationUser(
        'authorization-view-denied@example.com',
    );

    $tenant = authorizationIntegrationTenant(
        'authorization-view-denied',
    );

    $token = loginAsUser($user);

    $this
        ->withToken($token)
        ->getJson(
            "/api/v1/platform/tenants/{$tenant->id}",
        )
        ->assertForbidden();
});
