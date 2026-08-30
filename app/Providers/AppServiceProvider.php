<?php

declare(strict_types=1);

namespace App\Providers;

use App\Support\Tenancy\TenantContext;
use App\Support\Tenancy\TenantResolver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            TenantContext::class,
            static fn(): TenantContext => new TenantContext
        );

        $this->app->bind(
            TenantResolver::class,
            static fn(): TenantResolver => new TenantResolver()
        );
    }

    public function boot(): void
    {
        //
    }
}
