<?php

declare(strict_types=1);

use App\Enums\TenantStatus;
use App\Models\Platform\Tenant;
use App\Support\Concurrency\OptimisticLockException;

test('concurrent update is rejected', function () {
    $tenant = Tenant::query()->create([
        'name' => 'Acme',
        'slug' => 'acme',
        'status' => TenantStatus::PENDING,
        'schema_name' => 'tenant_acme',
    ]);

    $first = Tenant::query()->findOrFail($tenant->id);
    $second = Tenant::query()->findOrFail($tenant->id);

    expect($first->row_version)->toBe(1)
        ->and($second->row_version)->toBe(1);

    $first->name = 'Acme Corporation';
    $first->save();

    expect($first->fresh()->row_version)->toBe(2);

    $second->name = 'Acme Limited';

    $second->save();
})->throws(OptimisticLockException::class);
