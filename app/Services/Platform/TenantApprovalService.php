<?php

declare(strict_types=1);

namespace App\Services\Platform;

use App\Enums\TenantStatus;
use App\Exceptions\ApplicationException;
use App\Jobs\Tenancy\ProvisionTenant;
use App\Models\Platform\Tenant;
use App\Models\Platform\User;
use Illuminate\Support\Facades\DB;

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
            throw new ApplicationException(
                sprintf(
                    'Tenant [%s] cannot be approved from status [%s].',
                    $tenant->id,
                    $tenant->status->value,
                ),
                422,
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

                ProvisionTenant::dispatch(
                    (string) $tenant->id
                )->afterCommit();

                return $tenant->fresh();
            }
        );
    }
}
