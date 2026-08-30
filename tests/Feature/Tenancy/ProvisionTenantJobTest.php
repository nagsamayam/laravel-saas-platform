<?php

declare(strict_types=1);

use App\Enums\TenantStatus;
use App\Jobs\Tenancy\ProvisionTenant;
use App\Models\Platform\Tenant;
use App\Services\Tenancy\TenantProvisioningService;
use App\Support\Tenancy\TenantMigrationRunnerContract;
use Illuminate\Support\Facades\Queue;

function queuedTenant(
    string $slug = 'queued',
): Tenant {
    return Tenant::query()->create([
        'name' => ucfirst($slug),
        'slug' => $slug,
        'status' => TenantStatus::APPROVED,
    ]);
}

it('dispatches a tenant provisioning job', function (): void {
    Queue::fake();

    $tenant = queuedTenant();

    ProvisionTenant::dispatch(
        $tenant->id
    );

    Queue::assertPushed(
        ProvisionTenant::class,
        fn (ProvisionTenant $job): bool => $job->tenantId === $tenant->id
    );
});

it('loads the tenant by id when handling the job', function (): void {
    $tenant = queuedTenant();

    $migrationRunner = Mockery::mock(
        TenantMigrationRunnerContract::class
    );

    $migrationRunner
        ->shouldReceive('run')
        ->once()
        ->with($tenant->schema_name);

    app()->instance(
        TenantMigrationRunnerContract::class,
        $migrationRunner,
    );

    $job = new ProvisionTenant(
        $tenant->id
    );

    $job->handle(
        app(TenantProvisioningService::class)
    );

    expect($tenant->fresh()->status)
        ->toBe(TenantStatus::ACTIVE);
});

it('does nothing when the tenant no longer exists', function (): void {
    $job = new ProvisionTenant(
        '00000000-0000-4000-8000-000000000001',
    );

    $migrationRunner = Mockery::mock(
        TenantMigrationRunnerContract::class
    );

    $migrationRunner
        ->shouldNotReceive('run');

    app()->instance(
        TenantMigrationRunnerContract::class,
        $migrationRunner,
    );

    $job->handle(
        app(TenantProvisioningService::class)
    );

    expect(
        Tenant::query()
            ->whereKey('00000000-0000-4000-8000-000000000001')
            ->exists()
    )->toBeFalse();
});

it('uses a tenant-specific overlap key', function (): void {
    $tenant = queuedTenant();

    $job = new ProvisionTenant(
        $tenant->id
    );

    expect($job->middleware())
        ->toHaveCount(1);
});
