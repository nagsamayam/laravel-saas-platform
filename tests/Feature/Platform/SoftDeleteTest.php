<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Enums\TenantStatus;
use App\Models\Platform\Tenant;
use Tests\TestCase;

class SoftDeleteTest extends TestCase
{
    public function test_tenant_is_soft_deleted(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Acme',
            'slug' => 'acme',
            'status' => TenantStatus::PENDING,
            'schema_name' => 'tenant_acme',
        ]);

        $tenant->delete();

        $this->assertSoftDeleted('tenants', [
            'id' => $tenant->id,
        ]);

        $this->assertNull(
            Tenant::query()->find($tenant->id)
        );

        $this->assertNotNull(
            Tenant::withTrashed()->find($tenant->id)
        );
    }
}
