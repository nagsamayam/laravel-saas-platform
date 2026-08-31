<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Platform;

use App\Http\Controllers\Controller;
use App\Http\Resources\Platform\TenantResource;
use App\Models\Platform\Tenant;
use App\Services\Platform\TenantApprovalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class TenantApprovalController extends Controller
{
    public function __construct(
        private readonly TenantApprovalService $approvalService,
    ) {}

    public function approve(
        Request $request,
        Tenant $tenant,
    ): JsonResponse {
        Gate::authorize(
            'approve',
            $tenant
        );

        $tenant = $this->approvalService->approve(
            tenant: $tenant,
            approvedBy: $request->user('api'),
        );

        return (new TenantResource($tenant))
            ->response()
            ->setStatusCode(200);
    }
}
