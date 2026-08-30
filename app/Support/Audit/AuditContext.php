<?php

declare(strict_types=1);

namespace App\Support\Audit;

use Illuminate\Support\Facades\Auth;

final class AuditContext
{
    private static ?string $actorId = null;

    public static function setActor(?string $actorId): void
    {
        self::$actorId = $actorId;
    }

    public static function actorId(): ?string
    {
        return self::$actorId
            ?? Auth::id();
    }

    public static function clear(): void
    {
        self::$actorId = null;
    }
}
