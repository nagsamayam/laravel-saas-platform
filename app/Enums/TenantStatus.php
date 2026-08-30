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
}
