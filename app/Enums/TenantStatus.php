<?php

declare(strict_types=1);

namespace App\Enums;

enum TenantStatus: string
{
    case PENDING = 'pending';

    case APPROVED = 'approved';

    case PROVISIONING = 'provisioning';

    case ACTIVE = 'active';

    case PROVISIONING_FAILED = 'provisioning_failed';

    case SUSPENDED = 'suspended';

    public function isProvisionable(): bool
    {
        return in_array($this, [
            self::APPROVED,
            self::PROVISIONING_FAILED,
        ], true);
    }

    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }
}
