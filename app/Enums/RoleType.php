<?php

declare(strict_types=1);

namespace App\Enums;

enum RoleType: string
{
    case PLATFORM = 'platform';

    case TENANT = 'tenant';
}
