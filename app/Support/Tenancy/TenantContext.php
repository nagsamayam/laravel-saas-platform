<?php

declare(strict_types=1);

namespace App\Support\Tenancy;

use App\Models\Platform\Tenant;
use LogicException;

final class TenantContext
{
    private ?Tenant $tenant = null;

    public function set(Tenant $tenant): void
    {
        if ($this->tenant !== null) {
            throw new LogicException(
                'Tenant context has already been established.'
            );
        }

        $this->tenant = $tenant;
    }

    public function setForce(Tenant $tenant): void
    {
        $this->tenant = $tenant;
    }

    public function get(): Tenant
    {
        if ($this->tenant === null) {
            throw new LogicException(
                'Tenant context has not been established.'
            );
        }

        return $this->tenant;
    }

    public function id(): string
    {
        return (string) $this->get()->getKey();
    }

    public function schema(): string
    {
        return $this->get()->schema_name;
    }

    public function hasTenant(): bool
    {
        return $this->tenant !== null;
    }

    public function clear(): void
    {
        $this->tenant = null;
    }
}
