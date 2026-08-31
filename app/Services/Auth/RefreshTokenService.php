<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\Platform\RefreshToken;
use App\Models\Platform\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

final class RefreshTokenService
{
    private const int TTL_DAYS = 7;

    public function issue(User $user): string
    {
        $plainToken = Str::random(96);

        RefreshToken::query()->create([
            'user_id' => $user->id,
            'token_hash' => $this->hash($plainToken),
            'expires_at' => now()->addDays(self::TTL_DAYS),
            'created_at' => now(),
        ]);

        return $plainToken;
    }

    public function rotate(
        string $plainToken,
    ): array {
        return DB::transaction(
            function () use ($plainToken): array {
                $token = RefreshToken::query()
                    ->where(
                        'token_hash',
                        $this->hash($plainToken),
                    )
                    ->lockForUpdate()
                    ->first();

                if (
                    $token === null
                    || ! $token->isUsable()
                ) {
                    throw new RuntimeException(
                        'Invalid refresh token.'
                    );
                }

                $user = $token->user;

                $newPlainToken = Str::random(96);

                $newToken = RefreshToken::query()->create([
                    'user_id' => $user->id,
                    'token_hash' => $this->hash($newPlainToken),
                    'expires_at' => now()->addDays(self::TTL_DAYS),
                    'created_at' => now(),
                ]);

                $token->update([
                    'revoked_at' => now(),
                    'replaced_by' => $newToken->id,
                ]);

                return [
                    'user' => $user,
                    'refresh_token' => $newPlainToken,
                ];
            }
        );
    }

    public function revoke(
        string $plainToken,
    ): void {
        RefreshToken::query()
            ->where(
                'token_hash',
                $this->hash($plainToken),
            )
            ->whereNull('revoked_at')
            ->update([
                'revoked_at' => now(),
            ]);
    }

    private function hash(
        string $plainToken,
    ): string {
        return hash(
            'sha256',
            $plainToken,
        );
    }
}
