<?php

declare(strict_types=1);

use App\Enums\RoleType;
use App\Enums\TenantStatus;
use App\Enums\UserStatus;
use App\Jobs\Tenancy\ProvisionTenant;
use App\Models\Platform\Role;
use App\Models\Platform\User;
use App\Services\Platform\TenantApprovalService;
use App\Services\Platform\TenantOnboardingService;
use App\Services\Tenancy\TenantProvisioningService;
use App\Support\Tenancy\TenantMigrationRunner;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;

function lifecycleIntegrationAdmin(
    string $email = 'lifecycle-integration@example.com',
): User {
    $user = User::query()->create([
        'name' => 'Lifecycle Integration Admin',
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

it('completes the tenant lifecycle from onboarding to active', function (): void {
    Queue::fake();

    $admin = lifecycleIntegrationAdmin();

    $tenant = app(TenantOnboardingService::class)->create(
        name: 'Integration Tenant',
        slug: 'integration-tenant',
        requestedBy: $admin,
    );

    expect($tenant->status)
        ->toBe(TenantStatus::PENDING);

    expect($tenant->schema_name)
        ->toBe('tenant_integration_tenant');

    $tenant = app(TenantApprovalService::class)->approve(
        tenant: $tenant,
        approvedBy: $admin,
    );

    expect($tenant->status)
        ->toBe(TenantStatus::APPROVED);

    Queue::assertPushed(
        ProvisionTenant::class,
        function (ProvisionTenant $job) use ($tenant): bool {
            return $job->tenantId === $tenant->id;
        },
    );

    Queue::fake();

    $tenant = app(TenantProvisioningService::class)
        ->provision($tenant);

    expect($tenant->status)
        ->toBe(TenantStatus::ACTIVE);

    expect($tenant->provisioned_at)
        ->not->toBeNull();

    expect(
        DB::select(
            'SELECT schema_name
             FROM information_schema.schemata
             WHERE schema_name = ?',
            [$tenant->schema_name],
        )
    )->not->toBeEmpty();

    app(TenantMigrationRunner::class)
        ->dropSchema($tenant->schema_name);
});

it('does not allow a normal user to approve a tenant', function (): void {
    $admin = lifecycleIntegrationAdmin(
        'lifecycle-owner@example.com',
    );

    $normalUser = User::query()->create([
        'name' => 'Normal User',
        'email' => 'lifecycle-normal@example.com',
        'password' => Hash::make('password'),
        'status' => UserStatus::ACTIVE,
    ]);

    $tenant = app(TenantOnboardingService::class)->create(
        name: 'Protected Tenant',
        slug: 'protected-tenant',
        requestedBy: $admin,
    );

    $this
        ->withToken(loginAsUser($normalUser))
        ->postJson(
            "/api/v1/platform/tenants/{$tenant->id}/approve"
        )
        ->assertForbidden();

    expect($tenant->fresh()->status)
        ->toBe(TenantStatus::PENDING);
});

it('does not expose tenant schema through the lifecycle api', function (): void {
    $admin = lifecycleIntegrationAdmin(
        'lifecycle-api@example.com',
    );

    $tenant = app(TenantOnboardingService::class)->create(
        name: 'Hidden Schema Tenant',
        slug: 'hidden-schema-integration',
        requestedBy: $admin,
    );

    $this
        ->withToken(loginAsUser($admin))
        ->getJson(
            "/api/v1/platform/tenants/{$tenant->id}"
        )
        ->assertOk()
        ->assertJsonMissingPath('data.schema_name');
});
