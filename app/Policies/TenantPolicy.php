<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Platform\Tenant;
use App\Models\Platform\User;
use App\Services\Tenancy\TenantAuthorizationService;

final class TenantPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canManagePlatform();
    }

    public function view(
        User $user,
        Tenant $tenant,
    ): bool {
        return app(TenantAuthorizationService::class)
            ->canAccessTenant(
                user: $user,
                tenant: $tenant,
            );
    }

    public function create(User $user): bool
    {
        return $user->canManagePlatform();
    }

    public function approve(
        User $user,
        Tenant $tenant,
    ): bool {
        return $user->canManagePlatform();
    }

    public function manage(
        User $user,
        Tenant $tenant,
    ): bool {
        if ($user->canManagePlatform()) {
            return true;
        }

        return app(TenantAuthorizationService::class)
            ->canManageTenant(
                user: $user,
                tenant: $tenant,
            );
    }

    public function use(
        User $user,
        Tenant $tenant,
    ): bool {
        if ($user->canManagePlatform()) {
            return true;
        }

        return app(TenantAuthorizationService::class)
            ->canAccessTenant(
                user: $user,
                tenant: $tenant,
            );
    }
}
