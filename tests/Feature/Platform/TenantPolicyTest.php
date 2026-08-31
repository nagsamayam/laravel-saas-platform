<?php

declare(strict_types=1);

use App\Enums\RoleType;
use App\Enums\TenantStatus;
use App\Enums\UserStatus;
use App\Models\Platform\Role;
use App\Models\Platform\Tenant;
use App\Models\Platform\User;
use App\Policies\TenantPolicy;
use Illuminate\Support\Facades\Hash;

function policyUser(
    string $email = 'policy@example.com',
): User {
    return User::query()->create([
        'name' => 'Policy User',
        'email' => $email,
        'password' => Hash::make('password'),
        'status' => UserStatus::ACTIVE,
    ]);
}

function platformAdminPolicyUser(
    string $email = 'platform-policy@example.com',
): User {
    $user = policyUser($email);

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

function policyTenant(
    string $slug = 'policy-tenant',
): Tenant {
    return Tenant::query()->create([
        'name' => 'Policy Tenant',
        'slug' => $slug,
        'status' => TenantStatus::PENDING,
    ]);
}

it('allows platform admins to view tenants', function (): void {
    $user = platformAdminPolicyUser();
    $tenant = policyTenant();

    $policy = new TenantPolicy;

    expect($policy->view($user, $tenant))
        ->toBeTrue();
});

it('allows platform admins to list tenants', function (): void {
    $user = platformAdminPolicyUser();

    $policy = new TenantPolicy;

    expect($policy->viewAny($user))
        ->toBeTrue();
});

it('allows platform admins to create tenants', function (): void {
    $user = platformAdminPolicyUser();

    $policy = new TenantPolicy;

    expect($policy->create($user))
        ->toBeTrue();
});

it('allows platform admins to approve tenants', function (): void {
    $user = platformAdminPolicyUser();
    $tenant = policyTenant();

    $policy = new TenantPolicy;

    expect($policy->approve($user, $tenant))
        ->toBeTrue();
});

it('rejects normal users from managing tenants', function (): void {
    $user = policyUser();
    $tenant = policyTenant();

    $policy = new TenantPolicy;

    expect($policy->viewAny($user))
        ->toBeFalse();

    expect($policy->view($user, $tenant))
        ->toBeFalse();

    expect($policy->create($user))
        ->toBeFalse();

    expect($policy->approve($user, $tenant))
        ->toBeFalse();
});
