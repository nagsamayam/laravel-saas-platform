<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Enums\TenantStatus;
use App\Models\Platform\Tenant;
use App\Support\Concurrency\OptimisticLockException;
use Tests\TestCase;

class OptimisticConcurrencyTest extends TestCase
{
    public function test_concurrent_update_is_rejected(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Acme',
            'slug' => 'acme',
            'status' => TenantStatus::PENDING,
            'schema_name' => 'tenant_acme',
        ]);

        $first = Tenant::query()->findOrFail($tenant->id);
        $second = Tenant::query()->findOrFail($tenant->id);

        $this->assertSame(1, $first->row_version);
        $this->assertSame(1, $second->row_version);

        $first->name = 'Acme Corporation';
        $first->save();

        $this->assertSame(
            2,
            $first->fresh()->row_version
        );

        $second->name = 'Acme Limited';

        $this->expectException(OptimisticLockException::class);

        $second->save();
    }
}
