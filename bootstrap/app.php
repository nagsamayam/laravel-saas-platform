<?php

use App\Exceptions\ApplicationException;
use App\Http\Middleware\RequirePlatformAdmin;
use App\Http\Middleware\RequireTenantMembership;
use App\Http\Middleware\ResolveTenant;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'tenant' => ResolveTenant::class,
            'platform.admin' => RequirePlatformAdmin::class,
            'tenant.member' => RequireTenantMembership::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (
            AccessDeniedHttpException $exception,
            Request $request,
        ) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $exception->getMessage()
                        ?: 'Forbidden.',
                ], 403);
            }
        });

        $exceptions->render(function (
            NotFoundHttpException $exception,
            Request $request,
        ) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Resource not found.',
                ], 404);
            }
        });

        $exceptions->render(function (
            ApplicationException $exception,
            Request $request,
        ) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $exception->getMessage(),
                ], $exception->statusCode());
            }
        });
    })->create();
