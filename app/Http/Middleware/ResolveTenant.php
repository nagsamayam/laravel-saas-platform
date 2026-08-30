<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Tenancy\TenantContext;
use App\Support\Tenancy\TenantDatabaseManager;
use App\Support\Tenancy\TenantResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ResolveTenant
{
    public function __construct(
        private readonly TenantResolver $tenantResolver,
        private readonly TenantContext $tenantContext,
        private readonly TenantDatabaseManager $tenantDatabaseManager,
    ) {}

    public function handle(
        Request $request,
        Closure $next,
    ): Response {
        $tenant = $this->tenantResolver->resolve($request);

        $this->tenantContext->set($tenant);

        try {
            $this->tenantDatabaseManager->connectToTenant(
                $this->tenantContext
            );

            return $next($request);
        } finally {
            $this->reset();
        }
    }

    private function reset(): void
    {
        try {
            $this->tenantDatabaseManager->disconnect();
        } finally {
            $this->tenantContext->clear();
        }
    }
}
