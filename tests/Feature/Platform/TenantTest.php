<?php

declare(strict_types=1);

use App\Enums\TenantStatus;
use App\Models\Platform\Tenant;

use function Pest\Laravel\assertDatabaseHas;

test('tenant can be created', function () {
    $tenant = Tenant::query()->create([
        'name' => 'Acme Corporation',
        'slug' => 'acme',
        'status' => TenantStatus::PENDING,
        'schema_name' => 'tenant_acme',
    ]);

    assertDatabaseHas('tenants', [
        'id' => $tenant->id,
        'slug' => 'acme',
        'status' => TenantStatus::PENDING->value,
        'schema_name' => 'tenant_acme',
    ]);
});

test('active scope returns only active tenants', function () {
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

    expect(Tenant::query()->active()->get())->toHaveCount(1);
});
