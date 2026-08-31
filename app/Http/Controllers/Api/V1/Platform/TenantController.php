<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\CreateTenantRequest;
use App\Http\Resources\Platform\TenantResource;
use App\Models\Platform\Tenant;
use App\Services\Platform\TenantOnboardingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

final class TenantController extends Controller
{
    public function __construct(
        private readonly TenantOnboardingService $onboardingService,
    ) {}

    public function store(
        CreateTenantRequest $request,
    ): JsonResponse {
        Gate::authorize(
            'create',
            Tenant::class
        );

        $tenant = $this->onboardingService->create(
            name: $request->validated('name'),
            slug: $request->validated('slug'),
            requestedBy: $request->user('api'),
        );

        return (new TenantResource($tenant))
            ->response()
            ->setStatusCode(201);
    }
}
