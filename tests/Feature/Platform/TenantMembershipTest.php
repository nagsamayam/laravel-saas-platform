<?php

declare(strict_types=1);

use App\Enums\TenantMembershipStatus;
use App\Enums\TenantStatus;
use App\Enums\UserStatus;
use App\Models\Platform\Tenant;
use App\Models\Platform\TenantUser;
use App\Models\Platform\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Hash;

test('user can belong to multiple tenants', function () {
    $user = User::query()->create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => Hash::make('password'),
        'status' => UserStatus::ACTIVE,
    ]);

    $tenantA = Tenant::query()->create([
        'name' => 'Tenant A',
        'slug' => 'tenant-a',
        'status' => TenantStatus::ACTIVE,
        'schema_name' => 'tenant_a',
    ]);

    $tenantB = Tenant::query()->create([
        'name' => 'Tenant B',
        'slug' => 'tenant-b',
        'status' => TenantStatus::ACTIVE,
        'schema_name' => 'tenant_b',
    ]);

    TenantUser::query()->create([
        'tenant_id' => $tenantA->id,
        'user_id' => $user->id,
        'status' => TenantMembershipStatus::ACTIVE,
    ]);

    TenantUser::query()->create([
        'tenant_id' => $tenantB->id,
        'user_id' => $user->id,
        'status' => TenantMembershipStatus::ACTIVE,
    ]);

    expect($user->fresh()->tenants)->toHaveCount(2)
        ->and($user->fresh()->tenantMemberships)->toHaveCount(2);
});

test('duplicate tenant membership is rejected', function () {
    $user = User::query()->create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => Hash::make('password'),
        'status' => UserStatus::ACTIVE,
    ]);

    $tenant = Tenant::query()->create([
        'name' => 'Tenant A',
        'slug' => 'tenant-a',
        'status' => TenantStatus::ACTIVE,
        'schema_name' => 'tenant_a',
    ]);

    TenantUser::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'status' => TenantMembershipStatus::ACTIVE,
    ]);

    TenantUser::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'status' => TenantMembershipStatus::ACTIVE,
    ]);
})->throws(QueryException::class);
