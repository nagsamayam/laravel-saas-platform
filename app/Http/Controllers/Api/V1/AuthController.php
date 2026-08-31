<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Platform\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

final class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => [
                'required',
                'email',
            ],

            'password' => [
                'required',
                'string',
            ],
        ]);

        if (! $token = JWTAuth::attempt($credentials)) {
            return response()->json([
                'message' => 'Invalid credentials.',
            ], 401);
        }

        return $this->tokenResponse(
            $token
        );
    }

    public function me(Request $request): JsonResponse
    {
        return (new UserResource(
            $request->user('api')
        ))
            ->response()
            ->setStatusCode(200);
    }

    public function logout(): JsonResponse
    {
        JWTAuth::invalidate(
            JWTAuth::getToken()
        );

        return response()->json([
            'message' => 'Successfully logged out.',
        ]);
    }

    private function tokenResponse(
        string $token,
    ): JsonResponse {
        return response()->json([
            'data' => [
                'access_token' => $token,
                'token_type' => 'Bearer',
                'expires_in' => JWTAuth::factory()
                    ->getTTL() * 60,
            ],
        ]);
    }
}
