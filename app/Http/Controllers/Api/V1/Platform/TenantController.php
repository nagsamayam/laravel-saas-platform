<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\CreateTenantRequest;
use App\Http\Resources\Platform\TenantResource;
use App\Services\Platform\TenantOnboardingService;
use Illuminate\Http\JsonResponse;

final class TenantController extends Controller
{
    public function __construct(
        private readonly TenantOnboardingService $onboardingService,
    ) {}

    public function store(
        CreateTenantRequest $request,
    ): JsonResponse {
        $tenant = $this->onboardingService->create(
            name: $request->validated('name'),
            slug: $request->validated('slug'),
            requestedBy: $request->user(),
        );

        return (new TenantResource($tenant))
            ->response()
            ->setStatusCode(201);
    }
}
