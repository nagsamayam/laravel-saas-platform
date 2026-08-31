<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Platform\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class RequirePlatformAdmin
{
    public function handle(
        Request $request,
        Closure $next,
    ): Response {
        $user = $request->user();

        if (! $user instanceof User) {
            throw new AccessDeniedHttpException(
                'Platform administrator access is required.'
            );
        }

        if (! $user->isPlatformAdmin()) {
            throw new AccessDeniedHttpException(
                'Platform administrator access is required.'
            );
        }

        return $next($request);
    }
}
