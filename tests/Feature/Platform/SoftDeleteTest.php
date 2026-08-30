<?php

declare(strict_types=1);

use App\Enums\TenantStatus;
use App\Models\Platform\Tenant;

use function Pest\Laravel\assertSoftDeleted;

test('tenant is soft deleted', function () {
    $tenant = Tenant::query()->create([
        'name' => 'Acme',
        'slug' => 'acme',
        'status' => TenantStatus::PENDING,
        'schema_name' => 'tenant_acme',
    ]);

    $tenant->delete();

    assertSoftDeleted('tenants', [
        'id' => $tenant->id,
    ]);

    expect(Tenant::query()->find($tenant->id))->toBeNull()
        ->and(Tenant::withTrashed()->find($tenant->id))->not->toBeNull();
});
