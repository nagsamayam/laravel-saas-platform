<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Enums\TenantMembershipStatus;
use App\Enums\TenantStatus;
use App\Enums\UserStatus;
use App\Models\Platform\Tenant;
use App\Models\Platform\TenantUser;
use App\Models\Platform\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TenantMembershipTest extends TestCase
{
    public function test_user_can_belong_to_multiple_tenants(): void
    {
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

        $this->assertCount(2, $user->fresh()->tenants);
        $this->assertCount(2, $user->fresh()->tenantMemberships);
    }

    public function test_duplicate_tenant_membership_is_rejected(): void
    {
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

        $this->expectException(QueryException::class);

        TenantUser::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'status' => TenantMembershipStatus::ACTIVE,
        ]);
    }
}
