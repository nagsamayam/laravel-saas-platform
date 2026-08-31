<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Platform\Tenant;
use App\Models\Platform\User;
use App\Services\Tenancy\TenantAuthorizationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class RequireTenantMembership
{
    public function __construct(
        private readonly TenantAuthorizationService $authorization,
    ) {}

    public function handle(
        Request $request,
        Closure $next,
    ): Response {
        $user = $request->user('api');

        if (! $user instanceof User) {
            throw new AccessDeniedHttpException(
                'Authenticated user is required.',
            );
        }

        $tenant = $request->attributes->get('tenant');

        if (! $tenant instanceof Tenant) {
            throw new AccessDeniedHttpException(
                'Tenant context is required.',
            );
        }

        if (! $this->authorization->canAccessTenant(
            user: $user,
            tenant: $tenant,
        )) {
            throw new AccessDeniedHttpException(
                'User does not have access to this tenant.',
            );
        }

        return $next($request);
    }
}
