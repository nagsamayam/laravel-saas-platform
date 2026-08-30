<?php

declare(strict_types=1);

namespace App\Providers;

use App\Support\Tenancy\TenantContext;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            TenantContext::class,
            static fn (): TenantContext => new TenantContext
        );
    }

    public function boot(): void
    {
        //
    }
}
