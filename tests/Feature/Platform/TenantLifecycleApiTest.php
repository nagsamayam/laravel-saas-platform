<?php

declare(strict_types=1);

use App\Enums\RoleType;
use App\Enums\TenantStatus;
use App\Enums\UserStatus;
use App\Models\Platform\Role;
use App\Models\Platform\Tenant;
use App\Models\Platform\User;
use Illuminate\Support\Facades\Hash;

function lifecycleApiUser(
    string $email = 'lifecycle-api@example.com',
): User {
    $user = User::query()->create([
        'name' => 'Lifecycle API Admin',
        'email' => $email,
        'password' => Hash::make('password'),
        'status' => UserStatus::ACTIVE,
    ]);

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

function lifecycleApiTenant(
    string $slug,
    TenantStatus $status = TenantStatus::PENDING,
): Tenant {
    return Tenant::query()->create([
        'name' => ucfirst($slug),
        'slug' => $slug,
        'status' => $status,
    ]);
}

it('lists tenants for a platform administrator', function (): void {
    $user = lifecycleApiUser();

    lifecycleApiTenant('tenant-one');
    lifecycleApiTenant('tenant-two');

    $this
        ->actingAs($user)
        ->getJson('/api/v1/platform/tenants')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'slug',
                    'status',
                    'created_by',
                    'updated_by',
                    'created_at',
                    'updated_at',
                ],
            ],
            'links',
            'meta',
        ]);
});

it('returns a single tenant', function (): void {
    $user = lifecycleApiUser();

    $tenant = lifecycleApiTenant(
        'single-tenant',
        TenantStatus::APPROVED,
    );

    $this
        ->actingAs($user)
        ->getJson(
            "/api/v1/platform/tenants/{$tenant->id}"
        )
        ->assertOk()
        ->assertJsonPath(
            'data.id',
            $tenant->id,
        )
        ->assertJsonPath(
            'data.name',
            $tenant->name,
        )
        ->assertJsonPath(
            'data.slug',
            $tenant->slug,
        )
        ->assertJsonPath(
            'data.status',
            TenantStatus::APPROVED->value,
        );
});

it('does not expose the tenant schema name', function (): void {
    $user = lifecycleApiUser();

    $tenant = lifecycleApiTenant(
        'hidden-schema',
    );

    $this
        ->actingAs($user)
        ->getJson(
            "/api/v1/platform/tenants/{$tenant->id}"
        )
        ->assertOk()
        ->assertJsonMissingPath(
            'data.schema_name'
        );
});

it('returns not found for an unknown tenant', function (): void {
    $user = lifecycleApiUser();

    $id = '00000000-0000-4000-8000-000000000001';

    $this
        ->actingAs($user)
        ->getJson(
            "/api/v1/platform/tenants/{$id}"
        )
        ->assertNotFound();
});

it('does not allow a normal user to list tenants', function (): void {
    $user = User::query()->create([
        'name' => 'Normal User',
        'email' => 'normal-lifecycle@example.com',
        'password' => Hash::make('password'),
        'status' => UserStatus::ACTIVE,
    ]);

    $this
        ->actingAs($user)
        ->getJson('/api/v1/platform/tenants')
        ->assertForbidden();
});

it('does not allow an unauthenticated user to list tenants', function (): void {
    $this
        ->getJson('/api/v1/platform/tenants')
        ->assertUnauthorized();
});
