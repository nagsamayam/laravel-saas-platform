<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\Platform\TenantApprovalController;
use App\Http\Controllers\Api\V1\Platform\TenantController;
use App\Http\Controllers\Api\V1\Platform\TenantLifecycleController;
use App\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class)
    ->name('health');

Route::prefix('v1')
    ->group(function (): void {
        Route::prefix('auth')
            ->group(function (): void {
                Route::post(
                    'login',
                    [AuthController::class, 'login']
                );

                Route::middleware('auth:api')
                    ->group(function (): void {
                        Route::post(
                            'refresh',
                            [AuthController::class, 'refresh']
                        );
                        Route::get(
                            'me',
                            [AuthController::class, 'me']
                        );

                        Route::post(
                            'logout',
                            [AuthController::class, 'logout']
                        );
                    });
            });
        Route::prefix('platform')
            ->middleware(['auth:api', 'platform.admin'])
            ->group(function (): void {
                Route::post(
                    'tenants',
                    [TenantController::class, 'store']
                );

                Route::post(
                    'tenants/{tenant}/approve',
                    [TenantApprovalController::class, 'approve']
                );

                Route::get(
                    'tenants',
                    [TenantLifecycleController::class, 'index']
                );

                Route::get(
                    'tenants/{tenant}',
                    [TenantLifecycleController::class, 'show']
                );
            });
    });
