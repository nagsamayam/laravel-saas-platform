<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Enums\TenantStatus;
use App\Models\Platform\Tenant;
use Tests\TestCase;

class TenantTest extends TestCase
{
    public function test_tenant_can_be_created(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Acme Corporation',
            'slug' => 'acme',
            'status' => TenantStatus::PENDING,
            'schema_name' => 'tenant_acme',
        ]);

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'slug' => 'acme',
            'status' => TenantStatus::PENDING->value,
            'schema_name' => 'tenant_acme',
        ]);
    }

    public function test_active_scope_returns_only_active_tenants(): void
    {
        Tenant::query()->create([
            'name' => 'Active Tenant',
            'slug' => 'active-tenant',
            'status' => TenantStatus::ACTIVE,
            'schema_name' => 'tenant_active',
        ]);

        Tenant::query()->create([
            'name' => 'Pending Tenant',
            'slug' => 'pending-tenant',
            'status' => TenantStatus::PENDING,
            'schema_name' => 'tenant_pending',
        ]);

        $this->assertCount(
            1,
            Tenant::query()->active()->get()
        );
    }
}
