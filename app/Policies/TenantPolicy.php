<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Platform\Tenant;
use App\Models\Platform\User;

final class TenantPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canManagePlatform();
    }

    public function view(User $user, Tenant $tenant): bool
    {
        return $user->canManagePlatform();
    }

    public function create(User $user): bool
    {
        return $user->canManagePlatform();
    }

    public function approve(User $user, Tenant $tenant): bool
    {
        return $user->canManagePlatform();
    }
}
