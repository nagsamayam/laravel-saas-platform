<?php

declare(strict_types=1);

namespace App\Services\Tenancy;

use App\Enums\RoleType;
use App\Enums\TenantMembershipStatus;
use App\Models\Platform\Tenant;
use App\Models\Platform\User;

final class TenantAuthorizationService
{
    public function hasMembership(
        User $user,
        Tenant $tenant,
    ): bool {
        return $user->tenantMemberships()
            ->where('tenant_id', $tenant->id)
            ->where(
                'status',
                TenantMembershipStatus::ACTIVE->value,
            )
            ->exists();
    }

    public function isTenantAdmin(
        User $user,
        Tenant $tenant,
    ): bool {
        return $this->hasRole(
            user: $user,
            tenant: $tenant,
            roleSlug: 'tenant_admin',
        );
    }

    public function isTenantUser(
        User $user,
        Tenant $tenant,
    ): bool {
        return $this->hasRole(
            user: $user,
            tenant: $tenant,
            roleSlug: 'tenant_user',
        );
    }

    public function canAccessTenant(
        User $user,
        Tenant $tenant,
    ): bool {
        if ($user->canManagePlatform()) {
            return true;
        }

        if ($tenant->status->value !== 'active') {
            return false;
        }

        return $this->hasMembership(
            user: $user,
            tenant: $tenant,
        );
    }

    public function canManageTenant(
        User $user,
        Tenant $tenant,
    ): bool {
        if ($user->canManagePlatform()) {
            return true;
        }

        if ($tenant->status->value !== 'active') {
            return false;
        }

        return $this->isTenantAdmin(
            user: $user,
            tenant: $tenant,
        );
    }

    private function hasRole(
        User $user,
        Tenant $tenant,
        string $roleSlug,
    ): bool {
        return $user->tenantMemberships()
            ->where('tenant_id', $tenant->id)
            ->where(
                'status',
                TenantMembershipStatus::ACTIVE->value,
            )
            ->whereHas(
                'roles',
                function ($query) use ($roleSlug): void {
                    $query
                        ->where(
                            'roles.slug',
                            $roleSlug,
                        )
                        ->where(
                            'roles.type',
                            RoleType::TENANT->value,
                        );
                },
            )
            ->exists();
    }
}
