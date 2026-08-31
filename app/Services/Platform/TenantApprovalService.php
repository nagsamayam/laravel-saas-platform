<?php

declare(strict_types=1);

namespace App\Services\Platform;

use App\Enums\TenantStatus;
use App\Jobs\Tenancy\ProvisionTenant;
use App\Models\Platform\Tenant;
use App\Models\Platform\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class TenantApprovalService
{
    public function approve(
        Tenant $tenant,
        User $approvedBy,
    ): Tenant {
        if (
            $tenant->status === TenantStatus::APPROVED
            || $tenant->status === TenantStatus::ACTIVE
        ) {
            return $tenant->fresh();
        }

        if ($tenant->status !== TenantStatus::PENDING) {
            throw new RuntimeException(
                sprintf(
                    'Tenant [%s] cannot be approved from status [%s].',
                    $tenant->id,
                    $tenant->status->value,
                )
            );
        }

        return DB::transaction(
            function () use (
                $tenant,
                $approvedBy,
            ): Tenant {
                $tenant->status = TenantStatus::APPROVED;
                $tenant->updated_by = $approvedBy->id;

                $tenant->save();

                $tenantId = (string) $tenant->id;

                ProvisionTenant::dispatch(
                    $tenantId
                )->afterCommit();

                return $tenant->fresh();
            }
        );
    }
}
