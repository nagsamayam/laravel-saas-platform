<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Platform;

use App\Http\Controllers\Controller;
use App\Http\Resources\Platform\TenantResource;
use App\Models\Platform\Tenant;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class TenantLifecycleController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $tenants = Tenant::query()
            ->orderByDesc('created_at')
            ->paginate(25);

        return TenantResource::collection($tenants);
    }

    public function show(
        Tenant $tenant,
    ): TenantResource {
        return new TenantResource($tenant);
    }
}
