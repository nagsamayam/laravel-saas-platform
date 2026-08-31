<?php

declare(strict_types=1);

namespace App\Services\Platform;

use App\Enums\TenantStatus;
use App\Models\Platform\Tenant;
use App\Models\Platform\User;
use RuntimeException;

final class TenantApprovalService
{
    public function approve(
        Tenant $tenant,
        User $approvedBy,
    ): Tenant {
        if ($tenant->status === TenantStatus::APPROVED) {
            return $tenant->fresh();
        }

        if ($tenant->status === TenantStatus::ACTIVE) {
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

        $tenant->status = TenantStatus::APPROVED;
        $tenant->updated_by = $approvedBy->id;

        $tenant->save();

        return $tenant->fresh();
    }
}
