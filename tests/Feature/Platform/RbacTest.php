<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Enums\RoleType;
use App\Models\Platform\Permission;
use App\Models\Platform\Role;
use App\Models\Platform\Tenant;
use App\Models\Platform\TenantUser;
use App\Models\Platform\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RbacTest extends TestCase
{
    public function test_platform_role_can_have_permissions(): void
    {
        $role = Role::query()->create([
            'name' => 'Platform Administrator',
            'slug' => 'platform_admin',
            'type' => RoleType::PLATFORM,
            'is_system' => true,
        ]);

        $permission = Permission::query()->create([
            'name' => 'platform.tenants.approve',
            'resource' => 'tenants',
            'action' => 'approve',
            'is_system' => true,
        ]);

        $role->permissions()->attach($permission);

        $this->assertTrue(
            $role->fresh()->hasPermission(
                'platform.tenants.approve'
            )
        );
    }

    public function test_tenant_user_can_have_tenant_role(): void
    {
        $user = User::query()->create([
            'name' => 'Tenant User',
            'email' => 'user@example.com',
            'password' => Hash::make('password'),
        ]);

        $tenant = Tenant::query()->create([
            'name' => 'Acme',
            'slug' => 'acme',
            'status' => 'active',
            'schema_name' => 'tenant_acme',
        ]);

        $membership = TenantUser::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'status' => 'active',
        ]);

        $role = Role::query()->create([
            'name' => 'Tenant User',
            'slug' => 'tenant_user',
            'type' => RoleType::TENANT,
            'is_system' => true,
        ]);

        $membership->roles()->attach($role);

        $this->assertTrue(
            $membership->fresh()->roles
                ->contains('id', $role->id)
        );
    }
}
