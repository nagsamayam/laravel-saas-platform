<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\RoleType;
use App\Models\Platform\Permission;
use App\Models\Platform\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            [
                'name' => 'platform.tenants.view',
                'resource' => 'tenants',
                'action' => 'view',
                'description' => 'View platform tenants.',
            ],
            [
                'name' => 'platform.tenants.create',
                'resource' => 'tenants',
                'action' => 'create',
                'description' => 'Create a tenant onboarding request.',
            ],
            [
                'name' => 'platform.tenants.approve',
                'resource' => 'tenants',
                'action' => 'approve',
                'description' => 'Approve a tenant.',
            ],
            [
                'name' => 'platform.tenants.suspend',
                'resource' => 'tenants',
                'action' => 'suspend',
                'description' => 'Suspend an active tenant.',
            ],

            [
                'name' => 'tenant.users.view',
                'resource' => 'users',
                'action' => 'view',
                'description' => 'View tenant users.',
            ],
            [
                'name' => 'tenant.users.create',
                'resource' => 'users',
                'action' => 'create',
                'description' => 'Create a tenant user.',
            ],
            [
                'name' => 'tenant.users.update',
                'resource' => 'users',
                'action' => 'update',
                'description' => 'Update a tenant user.',
            ],
            [
                'name' => 'tenant.users.remove',
                'resource' => 'users',
                'action' => 'remove',
                'description' => 'Remove a user from a tenant.',
            ],

            [
                'name' => 'tenant.projects.view',
                'resource' => 'projects',
                'action' => 'view',
                'description' => 'View tenant projects.',
            ],
            [
                'name' => 'tenant.projects.create',
                'resource' => 'projects',
                'action' => 'create',
                'description' => 'Create a tenant project.',
            ],
            [
                'name' => 'tenant.projects.update',
                'resource' => 'projects',
                'action' => 'update',
                'description' => 'Update a tenant project.',
            ],
            [
                'name' => 'tenant.projects.delete',
                'resource' => 'projects',
                'action' => 'delete',
                'description' => 'Delete a tenant project.',
            ],
        ];

        foreach ($permissions as $permissionData) {
            Permission::query()->updateOrCreate(
                [
                    'name' => $permissionData['name'],
                ],
                [
                    'resource' => $permissionData['resource'],
                    'action' => $permissionData['action'],
                    'description' => $permissionData['description'],
                    'is_system' => true,
                ],
            );
        }

        $roles = [
            [
                'name' => 'Platform Administrator',
                'slug' => 'platform_admin',
                'type' => RoleType::PLATFORM,
                'description' => 'Full platform administration access.',
                'permissions' => [
                    'platform.tenants.view',
                    'platform.tenants.create',
                    'platform.tenants.approve',
                    'platform.tenants.suspend',
                ],
            ],
            [
                'name' => 'Tenant Administrator',
                'slug' => 'tenant_admin',
                'type' => RoleType::TENANT,
                'description' => 'Full administration access within a tenant.',
                'permissions' => [
                    'tenant.users.view',
                    'tenant.users.create',
                    'tenant.users.update',
                    'tenant.users.remove',
                    'tenant.projects.view',
                    'tenant.projects.create',
                    'tenant.projects.update',
                    'tenant.projects.delete',
                ],
            ],
            [
                'name' => 'Tenant User',
                'slug' => 'tenant_user',
                'type' => RoleType::TENANT,
                'description' => 'Standard tenant user access.',
                'permissions' => [
                    'tenant.users.view',
                    'tenant.projects.view',
                    'tenant.projects.create',
                    'tenant.projects.update',
                ],
            ],
        ];

        foreach ($roles as $roleData) {
            $role = Role::query()->updateOrCreate(
                [
                    'slug' => $roleData['slug'],
                    'type' => $roleData['type']->value,
                ],
                [
                    'name' => $roleData['name'],
                    'description' => $roleData['description'],
                    'is_system' => true,
                ],
            );

            $permissionIds = Permission::query()
                ->whereIn('name', $roleData['permissions'])
                ->pluck('id');

            $role->permissions()->sync($permissionIds);
        }
    }
}
