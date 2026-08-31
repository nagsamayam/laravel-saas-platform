<?php

declare(strict_types=1);

namespace App\Services\Platform;

use App\Enums\TenantStatus;
use App\Models\Platform\Tenant;
use App\Models\Platform\User;
use Illuminate\Support\Facades\DB;

final class TenantOnboardingService
{
    public function create(
        string $name,
        string $slug,
        User $requestedBy,
    ): Tenant {
        return DB::transaction(
            function () use (
                $name,
                $slug,
                $requestedBy,
            ): Tenant {
                $tenant = Tenant::query()->create([
                    'name' => $name,
                    'slug' => $slug,
                    'status' => TenantStatus::PENDING,
                    'created_by' => $requestedBy->id,
                    'updated_by' => $requestedBy->id,
                ]);

                return $tenant->fresh();
            }
        );
    }
}
