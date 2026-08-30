<?php

declare(strict_types=1);

namespace App\Jobs\Tenancy;

use App\Models\Platform\Tenant;
use App\Services\Tenancy\TenantProvisioningService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Attributes\Backoff;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Throwable;

#[Tries(5)]
#[Backoff(30)]
#[Timeout(300)]
final class ProvisionTenant implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly string $tenantId,
    ) {}

    public function handle(
        TenantProvisioningService $provisioningService,
    ): void {
        $tenant = Tenant::query()->find($this->tenantId);

        if ($tenant === null) {
            return;
        }

        $provisioningService->provision($tenant);
    }

    public function middleware(): array
    {
        return [
            (new WithoutOverlapping("tenant-provisioning:{$this->tenantId}"))
                ->expireAfter(600),
        ];
    }

    public function failed(Throwable $exception): void
    {
        /*
         * The provisioning service already records
         * PROVISIONING_FAILED for application failures.
         *
         * This method is intentionally kept free of
         * tenant database operations.
         */
    }
}
