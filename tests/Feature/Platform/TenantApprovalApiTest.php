<?php

declare(strict_types=1);

use App\Enums\RoleType;
use App\Enums\TenantStatus;
use App\Enums\UserStatus;
use App\Jobs\Tenancy\ProvisionTenant;
use App\Models\Platform\Role;
use App\Models\Platform\Tenant;
use App\Models\Platform\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;

function approvalApiUser(
    string $email = 'approval-api@example.com',
): User {
    $user = User::query()->create([
        'name' => 'Approval API Admin',
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

function approvalApiTenant(
    string $slug = 'approval-api-tenant',
): Tenant {
    return Tenant::query()->create([
        'name' => 'Approval API Tenant',
        'slug' => $slug,
        'status' => TenantStatus::PENDING,
    ]);
}

it('approves a pending tenant through the api', function (): void {
    Queue::fake();

    $user = approvalApiUser();
    $tenant = approvalApiTenant();

    $response = $this
        ->actingAs($user)
        ->postJson(
            "/api/v1/platform/tenants/{$tenant->id}/approve"
        );

    $response
        ->assertOk()
        ->assertJsonPath(
            'data.id',
            $tenant->id,
        )
        ->assertJsonPath(
            'data.status',
            TenantStatus::APPROVED->value,
        );

    expect($tenant->fresh()->status)
        ->toBe(TenantStatus::APPROVED);

    Queue::assertPushed(
        ProvisionTenant::class,
    );
});

it('records the approving administrator', function (): void {
    $user = approvalApiUser(
        'approval-audit@example.com',
    );

    $tenant = approvalApiTenant(
        'approval-audit',
    );

    $this
        ->actingAs($user)
        ->postJson(
            "/api/v1/platform/tenants/{$tenant->id}/approve"
        )
        ->assertOk();

    expect($tenant->fresh()->updated_by)
        ->toBe($user->id);
});

it('is idempotent when approving an already approved tenant', function (): void {
    $user = approvalApiUser(
        'approval-idempotent@example.com',
    );

    $tenant = Tenant::query()->create([
        'name' => 'Already Approved',
        'slug' => 'already-approved-api',
        'status' => TenantStatus::APPROVED,
    ]);

    $response = $this
        ->actingAs($user)
        ->postJson(
            "/api/v1/platform/tenants/{$tenant->id}/approve"
        );

    $response
        ->assertOk()
        ->assertJsonPath(
            'data.status',
            TenantStatus::APPROVED->value,
        );
});

it('does not allow a normal authenticated user to approve a tenant', function (): void {
    $user = User::query()->create([
        'name' => 'Normal User',
        'email' => 'normal-approval@example.com',
        'password' => Hash::make('password'),
        'status' => UserStatus::ACTIVE,
    ]);

    $tenant = approvalApiTenant(
        'unauthorized-approval',
    );

    $this
        ->actingAs($user)
        ->postJson(
            "/api/v1/platform/tenants/{$tenant->id}/approve"
        )
        ->assertForbidden();

    expect($tenant->fresh()->status)
        ->toBe(TenantStatus::PENDING);
});

it('does not allow an unauthenticated user to approve a tenant', function (): void {
    $tenant = approvalApiTenant(
        'unauthenticated-approval',
    );

    $this
        ->postJson(
            "/api/v1/platform/tenants/{$tenant->id}/approve"
        )
        ->assertUnauthorized();

    expect($tenant->fresh()->status)
        ->toBe(TenantStatus::PENDING);
});

it('returns not found for an unknown tenant', function (): void {
    $user = approvalApiUser(
        'approval-not-found@example.com',
    );

    $id = '00000000-0000-4000-8000-000000000001';

    $this
        ->actingAs($user)
        ->postJson(
            "/api/v1/platform/tenants/{$id}/approve"
        )
        ->assertNotFound();
});

it('returns a consistent error for an invalid tenant state', function (): void {
    $user = approvalApiUser(
        'invalid-state@example.com',
    );

    $tenant = approvalApiTenant(
        'invalid-state',
    );

    $tenant->update([
        'status' => TenantStatus::SUSPENDED,
    ]);

    $this
        ->actingAs($user)
        ->postJson(
            "/api/v1/platform/tenants/{$tenant->id}/approve"
        )
        ->assertUnprocessable()
        ->assertJson([
            'message' => "Tenant [{$tenant->id}] cannot be approved from status [suspended].",
        ]);
});
