<?php

declare(strict_types=1);

use App\Enums\RoleType;
use App\Enums\TenantStatus;
use App\Enums\UserStatus;
use App\Models\Platform\Role;
use App\Models\Platform\Tenant;
use App\Models\Platform\User;
use Illuminate\Support\Facades\Hash;

function onboardingUser(
    string $email = 'platform@example.com',
): User {
    $user = User::query()->create([
        'name' => 'Platform User',
        'email' => $email,
        'password' => Hash::make('password'),
        'status' => UserStatus::ACTIVE,
    ]);

    $role = Role::query()->firstOrCreate(
        [
            'slug' => 'platform_admin',
        ],
        [
            'name' => 'Platform Administrator',
            'type' => RoleType::PLATFORM,
            'is_system' => true,
        ],
    );

    $user->platformRoles()->attach($role);

    return $user;
}

function nonPlatformUser(
    string $email = 'user@example.com',
): User {
    return User::query()->create([
        'name' => 'Normal User',
        'email' => $email,
        'password' => Hash::make('password'),
        'status' => UserStatus::ACTIVE,
    ]);
}

it('creates a pending tenant', function (): void {
    $user = onboardingUser();

    $response = $this
        ->actingAs($user)
        ->postJson('/api/v1/platform/tenants', [
            'name' => 'Acme Corporation',
            'slug' => 'acme-corporation',
        ]);

    $response->assertCreated()
        ->assertJsonPath(
            'data.name',
            'Acme Corporation',
        )
        ->assertJsonPath(
            'data.slug',
            'acme-corporation',
        )
        ->assertJsonPath(
            'data.status',
            TenantStatus::PENDING->value,
        );

    $this->assertDatabaseHas('tenants', [
        'name' => 'Acme Corporation',
        'slug' => 'acme-corporation',
        'status' => TenantStatus::PENDING->value,
    ]);
});

it('generates the tenant schema automatically', function (): void {
    $user = onboardingUser();

    $this
        ->actingAs($user)
        ->postJson('/api/v1/platform/tenants', [
            'name' => 'Acme Corporation',
            'slug' => 'acme-corporation',
        ])
        ->assertCreated();

    $tenant = Tenant::query()
        ->where('slug', 'acme-corporation')
        ->firstOrFail();

    expect($tenant->schema_name)
        ->toBe('tenant_acme_corporation');
});

it('does not allow status to be supplied by the client', function (): void {
    $user = onboardingUser();

    $response = $this
        ->actingAs($user)
        ->postJson('/api/v1/platform/tenants', [
            'name' => 'Acme Corporation',
            'slug' => 'acme-status-test',
            'status' => TenantStatus::ACTIVE->value,
        ]);

    $response
        ->assertCreated()
        ->assertJsonPath(
            'data.status',
            TenantStatus::PENDING->value,
        );
});

it('does not expose the tenant schema name', function (): void {
    $user = onboardingUser();

    $response = $this
        ->actingAs($user)
        ->postJson('/api/v1/platform/tenants', [
            'name' => 'Acme Corporation',
            'slug' => 'acme-hidden-schema',
        ]);

    $response
        ->assertCreated()
        ->assertJsonMissing([
            'schema_name' => 'tenant_acme_hidden_schema',
        ]);
});

it('requires a tenant name', function (): void {
    $user = onboardingUser();

    $this
        ->actingAs($user)
        ->postJson('/api/v1/platform/tenants', [
            'slug' => 'missing-name',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'name',
        ]);
});

it('requires a valid tenant slug', function (): void {
    $user = onboardingUser();

    $this
        ->actingAs($user)
        ->postJson('/api/v1/platform/tenants', [
            'name' => 'Acme',
            'slug' => 'Invalid Slug!',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'slug',
        ]);
});

it('does not allow duplicate tenant slugs', function (): void {
    $user = onboardingUser();

    Tenant::query()->create([
        'name' => 'Existing Tenant',
        'slug' => 'existing-tenant',
        'status' => TenantStatus::PENDING,
    ]);

    $this
        ->actingAs($user)
        ->postJson('/api/v1/platform/tenants', [
            'name' => 'Another Tenant',
            'slug' => 'existing-tenant',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'slug',
        ]);
});

it('allows a platform administrator to create a tenant', function (): void {
    $user = onboardingUser();

    $this
        ->actingAs($user)
        ->postJson('/api/v1/platform/tenants', [
            'name' => 'Platform Tenant',
            'slug' => 'platform-tenant',
        ])
        ->assertCreated()
        ->assertJsonPath(
            'data.status',
            TenantStatus::PENDING->value,
        );
});

it('rejects an authenticated non-platform-admin user', function (): void {
    $user = nonPlatformUser();

    $this
        ->actingAs($user)
        ->postJson('/api/v1/platform/tenants', [
            'name' => 'Unauthorized Tenant',
            'slug' => 'unauthorized-tenant',
        ])
        ->assertForbidden();
});

it('rejects an unauthenticated user', function (): void {
    $this
        ->postJson('/api/v1/platform/tenants', [
            'name' => 'Unauthorized Tenant',
            'slug' => 'unauthorized-tenant',
        ])
        ->assertUnauthorized();
});
