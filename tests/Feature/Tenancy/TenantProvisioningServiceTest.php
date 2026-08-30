<?php

declare(strict_types=1);

use App\Enums\TenantStatus;
use App\Models\Platform\Tenant;
use App\Services\Tenancy\TenantProvisioningService;
use App\Support\Tenancy\TenantMigrationRunner;
use App\Support\Tenancy\TenantMigrationRunnerContract;
use Illuminate\Support\Facades\DB;
use RuntimeException;

function provisioningTenant(
    string $slug,
    TenantStatus $status = TenantStatus::APPROVED,
): Tenant {
    return Tenant::query()->create([
        'name' => ucfirst($slug),
        'slug' => $slug,
        'status' => $status,
    ]);
}

it('provisions an approved tenant', function (): void {
    $tenant = provisioningTenant('provision');

    $service = app(TenantProvisioningService::class);

    $result = $service->provision($tenant);

    expect($result->status)
        ->toBe(TenantStatus::ACTIVE);

    expect($result->schema_name)
        ->toBe('tenant_provision');

    expect($result->provisioning_started_at)
        ->not->toBeNull();

    expect($result->provisioned_at)
        ->not->toBeNull();

    $schemaExists = DB::selectOne(
        'SELECT EXISTS (
            SELECT 1
            FROM information_schema.schemata
            WHERE schema_name = ?
        ) AS exists',
        [$tenant->schema_name],
    );

    expect((bool) $schemaExists->exists)
        ->toBeTrue();

    $projectsExists = DB::selectOne(
        'SELECT EXISTS (
            SELECT 1
            FROM information_schema.tables
            WHERE table_schema = ?
            AND table_name = ?
        ) AS exists',
        [
            $tenant->schema_name,
            'projects',
        ],
    );

    expect((bool) $projectsExists->exists)
        ->toBeTrue();

    app(TenantMigrationRunner::class)
        ->dropSchema($tenant->schema_name);
});

it('does not reprovision an active tenant', function (): void {
    $tenant = provisioningTenant(
        'already-active',
        TenantStatus::ACTIVE,
    );

    $tenant->forceFill([
        'provisioned_at' => now()->subMinute(),
    ])->save();

    $originalProvisionedAt = $tenant->fresh()->provisioned_at;

    $result = app(TenantProvisioningService::class)
        ->provision($tenant->fresh());

    expect($result->status)
        ->toBe(TenantStatus::ACTIVE);

    expect($result->provisioned_at->equalTo(
        $originalProvisionedAt
    ))->toBeTrue();
});

it('can retry a failed provisioning tenant', function (): void {
    $tenant = provisioningTenant(
        'retry',
        TenantStatus::PROVISIONING_FAILED,
    );

    $result = app(TenantProvisioningService::class)
        ->provision($tenant);

    expect($result->status)
        ->toBe(TenantStatus::ACTIVE);

    app(TenantMigrationRunner::class)
        ->dropSchema($tenant->schema_name);
});

it('rejects a pending tenant', function (): void {
    $tenant = provisioningTenant(
        'pending',
        TenantStatus::PENDING,
    );

    app(TenantProvisioningService::class)
        ->provision($tenant);
})->throws(RuntimeException::class);

it('rejects a suspended tenant', function (): void {
    $tenant = provisioningTenant(
        'suspended',
        TenantStatus::SUSPENDED,
    );

    app(TenantProvisioningService::class)
        ->provision($tenant);
})->throws(RuntimeException::class);

it('marks tenant as provisioning failed when migration fails', function (): void {
    $tenant = provisioningTenant('failure');

    $migrationRunner = Mockery::mock(
        TenantMigrationRunnerContract::class
    );

    $migrationRunner
        ->shouldReceive('run')
        ->once()
        ->with($tenant->schema_name)
        ->andThrow(
            new RuntimeException('Migration failed.')
        );

    app()->instance(
        TenantMigrationRunnerContract::class,
        $migrationRunner,
    );

    expect(fn () => app(
        TenantProvisioningService::class
    )->provision($tenant))
        ->toThrow(RuntimeException::class);

    expect($tenant->fresh()->status)
        ->toBe(TenantStatus::PROVISIONING_FAILED);
});
