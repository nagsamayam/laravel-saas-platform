<?php

declare(strict_types=1);

namespace App\Support\Tenancy;

use App\Enums\RoleType;
use App\Enums\TenantMembershipStatus;
use App\Enums\TenantStatus;
use App\Models\Platform\Tenant;
use App\Models\Platform\TenantUser;
use App\Models\Platform\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class TenantResolver
{
    public function resolve(Request $request): Tenant
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            throw new AccessDeniedHttpException(
                'Authentication is required to resolve a tenant.'
            );
        }

        $tenantId = $this->resolveTenantId($request);

        $tenant = Tenant::query()
            ->whereKey($tenantId)
            ->where(
                'status',
                TenantStatus::ACTIVE->value
            )
            ->first();

        if ($tenant === null) {
            throw new NotFoundHttpException(
                'Tenant not found or inactive.'
            );
        }

        if ($this->isPlatformAdmin($user)) {
            return $tenant;
        }

        $this->ensureTenantMembership(
            $user,
            $tenant
        );

        return $tenant;
    }

    private function resolveTenantId(Request $request): string
    {
        $tenantId = $request->header('X-Tenant-ID');

        if ($tenantId === null || trim($tenantId) === '') {
            throw new BadRequestHttpException(
                'X-Tenant-ID header is required.'
            );
        }

        $tenantId = trim($tenantId);

        if (! Str::isUuid($tenantId)) {
            throw new BadRequestHttpException(
                'X-Tenant-ID must be a valid UUID.'
            );
        }

        return $tenantId;
    }

    private function isPlatformAdmin(User $user): bool
    {
        return $user->platformRoles()
            ->where('slug', 'platform_admin')
            ->where('type', RoleType::PLATFORM->value)
            ->exists();
    }

    private function ensureTenantMembership(
        User $user,
        Tenant $tenant,
    ): void {
        $membershipExists = TenantUser::query()
            ->where('tenant_id', $tenant->id)
            ->where('user_id', $user->id)
            ->where(
                'status',
                TenantMembershipStatus::ACTIVE->value
            )
            ->exists();

        if (! $membershipExists) {
            throw new AccessDeniedHttpException(
                'User does not have access to this tenant.'
            );
        }
    }
}
