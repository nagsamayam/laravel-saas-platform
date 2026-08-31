<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Platform\TenantApprovalController;
use App\Http\Controllers\Api\V1\Platform\TenantController;
use App\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class)
    ->name('health');

Route::prefix('v1')
    ->group(function (): void {
        Route::prefix('platform')
            ->middleware(['auth', 'platform.admin'])
            ->group(function (): void {
                Route::post(
                    'tenants',
                    [TenantController::class, 'store']
                );

                Route::post(
                    'tenants/{tenant}/approve',
                    [TenantApprovalController::class, 'approve']
                );
            });
    });
