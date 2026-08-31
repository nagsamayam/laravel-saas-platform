<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Platform\UserResource;
use App\Services\Auth\RefreshTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

final class AuthController extends Controller
{
    public function __construct(
        private readonly RefreshTokenService $refreshTokenService,
    ) {}

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

        $token = auth('api')->attempt($credentials);

        if (! is_string($token) || $token === '') {
            return response()->json([
                'message' => 'Invalid credentials.',
            ], 401);
        }

        $user = auth('api')->user();

        if ($user === null) {
            return response()->json([
                'message' => 'Authentication failed.',
            ], 401);
        }

        $refreshToken = $this->refreshTokenService->issue(
            $user,
        );

        return response()->json([
            'data' => [
                'access_token' => $token,
                'token_type' => 'Bearer',
                'expires_in' => JWTAuth::factory()
                    ->getTTL() * 60,
                'refresh_token' => $refreshToken,
                'refresh_expires_in' => 7 * 24 * 60 * 60,
            ],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return (new UserResource(
            $request->user('api')
        ))
            ->response()
            ->setStatusCode(200);
    }

    public function logout(
        Request $request,
    ): JsonResponse {
        $refreshToken = $request->input(
            'refresh_token'
        );

        if (
            is_string($refreshToken)
            && $refreshToken !== ''
        ) {
            $this->refreshTokenService->revoke(
                $refreshToken,
            );
        }

        auth('api')->logout();

        return response()->json([
            'message' => 'Successfully logged out.',
        ]);
    }

    public function refresh(
        Request $request,
    ): JsonResponse {
        $refreshToken = $request->validate([
            'refresh_token' => [
                'required',
                'string',
            ],
        ])['refresh_token'];

        $result = $this->refreshTokenService->rotate(
            $refreshToken,
        );

        $accessToken = auth('api')->login(
            $result['user'],
        );

        return response()->json([
            'data' => [
                'access_token' => $accessToken,
                'token_type' => 'Bearer',
                'expires_in' => JWTAuth::factory()
                    ->getTTL() * 60,
                'refresh_token' => $result['refresh_token'],
                'refresh_expires_in' => 7 * 24 * 60 * 60,
            ],
        ]);
    }
}
