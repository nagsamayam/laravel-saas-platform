<?php

declare(strict_types=1);

use App\Enums\RoleType;
use App\Enums\TenantMembershipStatus;
use App\Enums\TenantStatus;
use App\Enums\UserStatus;
use App\Models\Platform\Role;
use App\Models\Platform\Tenant;
use App\Models\Platform\User;
use App\Services\Tenancy\TenantAuthorizationService;
use Illuminate\Support\Facades\Hash;

function membershipAuthorizationUser(
    string $email,
): User {
    return User::query()->create([
        'name' => 'Tenant Authorization User',
        'email' => $email,
        'password' => Hash::make('password'),
        'status' => UserStatus::ACTIVE,
    ]);
}

function membershipAuthorizationTenant(
    string $slug,
): Tenant {
    return Tenant::query()->create([
        'name' => 'Membership Tenant',
        'slug' => $slug,
        'status' => TenantStatus::ACTIVE,
    ]);
}

function membershipAuthorizationRole(
    string $slug,
): Role {
    return Role::query()->firstOrCreate(
        [
            'slug' => $slug,
            'type' => RoleType::TENANT,
        ],
        [
            'name' => str($slug)
                ->replace('_', ' ')
                ->title()
                ->toString(),
            'is_system' => true,
        ],
    );
}

function assignTenantRole(
    User $user,
    Tenant $tenant,
    Role $role,
): void {
    $membership = $user->tenantMemberships()->create([
        'tenant_id' => $tenant->id,
        'status' => TenantMembershipStatus::ACTIVE,
    ]);

    $membership->roles()->attach(
        $role->id,
    );
}

it('recognizes an active tenant membership', function (): void {
    $user = membershipAuthorizationUser(
        'membership@example.com',
    );

    $tenant = membershipAuthorizationTenant(
        'membership-tenant',
    );

    $role = membershipAuthorizationRole(
        'tenant_user',
    );

    assignTenantRole(
        user: $user,
        tenant: $tenant,
        role: $role,
    );

    expect(
        app(TenantAuthorizationService::class)
            ->hasMembership($user, $tenant),
    )->toBeTrue();
});

it('recognizes a tenant administrator', function (): void {
    $user = membershipAuthorizationUser(
        'tenant-admin@example.com',
    );

    $tenant = membershipAuthorizationTenant(
        'tenant-admin-tenant',
    );

    $role = membershipAuthorizationRole(
        'tenant_admin',
    );

    assignTenantRole(
        user: $user,
        tenant: $tenant,
        role: $role,
    );

    expect(
        app(TenantAuthorizationService::class)
            ->isTenantAdmin($user, $tenant),
    )->toBeTrue();
});

it('recognizes a tenant user', function (): void {
    $user = membershipAuthorizationUser(
        'tenant-user@example.com',
    );

    $tenant = membershipAuthorizationTenant(
        'tenant-user-tenant',
    );

    $role = membershipAuthorizationRole(
        'tenant_user',
    );

    assignTenantRole(
        user: $user,
        tenant: $tenant,
        role: $role,
    );

    expect(
        app(TenantAuthorizationService::class)
            ->isTenantUser($user, $tenant),
    )->toBeTrue();
});

it('allows a tenant administrator to manage the tenant', function (): void {
    $user = membershipAuthorizationUser(
        'tenant-manager@example.com',
    );

    $tenant = membershipAuthorizationTenant(
        'tenant-manager-tenant',
    );

    $role = membershipAuthorizationRole(
        'tenant_admin',
    );

    assignTenantRole(
        user: $user,
        tenant: $tenant,
        role: $role,
    );

    expect(
        app(TenantAuthorizationService::class)
            ->canManageTenant($user, $tenant),
    )->toBeTrue();
});

it('does not allow a tenant user to manage the tenant', function (): void {
    $user = membershipAuthorizationUser(
        'tenant-user-manager@example.com',
    );

    $tenant = membershipAuthorizationTenant(
        'tenant-user-manager-tenant',
    );

    $role = membershipAuthorizationRole(
        'tenant_user',
    );

    assignTenantRole(
        user: $user,
        tenant: $tenant,
        role: $role,
    );

    expect(
        app(TenantAuthorizationService::class)
            ->canManageTenant($user, $tenant),
    )->toBeFalse();
});

it('does not allow a user from another tenant', function (): void {
    $user = membershipAuthorizationUser(
        'other-tenant@example.com',
    );

    $targetTenant = membershipAuthorizationTenant(
        'target-tenant',
    );

    $otherTenant = membershipAuthorizationTenant(
        'other-tenant',
    );

    $role = membershipAuthorizationRole(
        'tenant_user',
    );

    assignTenantRole(
        user: $user,
        tenant: $otherTenant,
        role: $role,
    );

    expect(
        app(TenantAuthorizationService::class)
            ->canAccessTenant(
                $user,
                $targetTenant,
            ),
    )->toBeFalse();
});

it('does not allow an invited membership to access a tenant', function (): void {
    $user = membershipAuthorizationUser(
        'invited@example.com',
    );

    $tenant = membershipAuthorizationTenant(
        'invited-tenant',
    );

    $role = membershipAuthorizationRole(
        'tenant_user',
    );

    $membership = $user->tenantMemberships()->create([
        'tenant_id' => $tenant->id,
        'status' => TenantMembershipStatus::INVITED,
    ]);

    $membership->roles()->attach(
        $role->id,
    );

    expect(
        app(TenantAuthorizationService::class)
            ->canAccessTenant(
                $user,
                $tenant,
            ),
    )->toBeFalse();
});

it('does not allow a suspended membership to access a tenant', function (): void {
    $user = membershipAuthorizationUser(
        'suspended@example.com',
    );

    $tenant = membershipAuthorizationTenant(
        'suspended-membership-tenant',
    );

    $role = membershipAuthorizationRole(
        'tenant_user',
    );

    $membership = $user->tenantMemberships()->create([
        'tenant_id' => $tenant->id,
        'status' => TenantMembershipStatus::SUSPENDED,
    ]);

    $membership->roles()->attach(
        $role->id,
    );

    expect(
        app(TenantAuthorizationService::class)
            ->canAccessTenant(
                $user,
                $tenant,
            ),
    )->toBeFalse();
});

it('does not allow membership access to an inactive tenant', function (): void {
    $user = membershipAuthorizationUser(
        'inactive-tenant@example.com',
    );

    $tenant = membershipAuthorizationTenant(
        'inactive-tenant',
    );

    $tenant->update([
        'status' => TenantStatus::SUSPENDED,
    ]);

    $role = membershipAuthorizationRole(
        'tenant_user',
    );

    assignTenantRole(
        user: $user,
        tenant: $tenant,
        role: $role,
    );

    expect(
        app(TenantAuthorizationService::class)
            ->canAccessTenant(
                $user,
                $tenant,
            ),
    )->toBeFalse();
});

it('allows a platform administrator to access an active tenant', function (): void {
    $user = membershipAuthorizationUser(
        'platform@example.com',
    );

    $tenant = membershipAuthorizationTenant(
        'platform-access-tenant',
    );

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

    expect(
        app(TenantAuthorizationService::class)
            ->canAccessTenant(
                $user,
                $tenant,
            ),
    )->toBeTrue();
});
