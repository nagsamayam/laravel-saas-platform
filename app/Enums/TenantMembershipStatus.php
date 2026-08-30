<?php

declare(strict_types=1);

namespace App\Enums;

enum TenantMembershipStatus: string
{
    case INVITED = 'invited';

    case ACTIVE = 'active';

    case SUSPENDED = 'suspended';

    case REMOVED = 'removed';
}
