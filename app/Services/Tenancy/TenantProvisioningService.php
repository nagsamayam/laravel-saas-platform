<?php

declare(strict_types=1);

namespace App\Services\Tenancy;

use App\Enums\TenantStatus;
use App\Models\Platform\Tenant;
use App\Support\Concurrency\OptimisticLockException;
use App\Support\Tenancy\TenantMigrationRunnerContract;
use RuntimeException;
use Throwable;

final class TenantProvisioningService
{
    public function __construct(
        private readonly TenantMigrationRunnerContract $migrationRunner,
    ) {}

    public function provision(Tenant $tenant): Tenant
    {
        if ($tenant->status === TenantStatus::ACTIVE) {
            return $tenant;
        }

        $this->validateTenant($tenant);

        if (! $this->claimProvisioning($tenant)) {
            return $tenant->fresh();
        }

        try {
            $this->migrationRunner->run(
                $tenant->schema_name
            );

            $this->markActive($tenant);

            return $tenant->fresh();
        } catch (Throwable $exception) {
            $this->markProvisioningFailed($tenant);

            throw $exception;
        }
    }

    private function validateTenant(Tenant $tenant): void
    {
        if (! $tenant->exists) {
            throw new RuntimeException(
                'Cannot provision a tenant that has not been persisted.'
            );
        }

        if (! $tenant->schema_name) {
            throw new RuntimeException(
                'Cannot provision a tenant without a schema name.'
            );
        }

        if (! $tenant->status->isProvisionable()) {
            throw new RuntimeException(
                sprintf(
                    'Tenant [%s] cannot be provisioned from status [%s].',
                    $tenant->id,
                    $tenant->status->value,
                )
            );
        }
    }

    private function claimProvisioning(Tenant $tenant): bool
    {
        $expectedVersion = $tenant->row_version;

        $tenant->status = TenantStatus::PROVISIONING;
        $tenant->provisioning_started_at = now();

        /*
         * HasOptimisticLock adds:
         *
         * WHERE row_version = expectedVersion
         *
         * and increments row_version.
         */
        try {
            $tenant->save();

            return true;
        } catch (OptimisticLockException) {
            return false;
        }
    }

    private function markActive(Tenant $tenant): void
    {
        $tenant->status = TenantStatus::ACTIVE;
        $tenant->provisioned_at = now();

        $tenant->save();
    }

    private function markProvisioningFailed(Tenant $tenant): void
    {
        $tenant->status = TenantStatus::PROVISIONING_FAILED;

        $tenant->save();
    }
}
