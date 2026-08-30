<?php

declare(strict_types=1);

use App\Support\Tenancy\TenantDatabaseManager;
use App\Support\Tenancy\TenantMigrationRunner;
use Illuminate\Support\Facades\DB;

function isolationSchema(string $name): string
{
    return 'tenant_isolation_'.$name;
}

function createIsolationTenant(
    TenantMigrationRunner $runner,
    string $schema,
): void {
    $runner->run($schema);
}

afterEach(function (): void {
    $runner = app(TenantMigrationRunner::class);

    foreach (
        [
            isolationSchema('a'),
            isolationSchema('b'),
        ] as $schema
    ) {
        $runner->dropSchema($schema);
    }
});

it('isolates tenant tables by postgres schema', function (): void {
    $runner = app(TenantMigrationRunner::class);

    $schemaA = isolationSchema('a');
    $schemaB = isolationSchema('b');

    createIsolationTenant($runner, $schemaA);
    createIsolationTenant($runner, $schemaB);

    $manager = app(TenantDatabaseManager::class);

    $manager->connect($schemaA);

    DB::table('projects')->insert([
        'id' => '10000000-0000-4000-8000-000000000001',
        'name' => 'Tenant A Project',
        'slug' => 'tenant-a-project',
        'row_version' => 1,
    ]);

    expect(
        DB::table('projects')
            ->where('slug', 'tenant-a-project')
            ->exists()
    )->toBeTrue();

    $manager->disconnect();

    $manager->connect($schemaB);

    expect(
        DB::table('projects')
            ->where('slug', 'tenant-a-project')
            ->exists()
    )->toBeFalse();

    DB::table('projects')->insert([
        'id' => '20000000-0000-4000-8000-000000000001',
        'name' => 'Tenant B Project',
        'slug' => 'tenant-b-project',
        'row_version' => 1,
    ]);

    expect(
        DB::table('projects')
            ->where('slug', 'tenant-b-project')
            ->exists()
    )->toBeTrue();

    expect(
        DB::table('projects')
            ->where('slug', 'tenant-a-project')
            ->exists()
    )->toBeFalse();

    $manager->disconnect();
});

it('resets search path after tenant disconnect', function (): void {
    $runner = app(TenantMigrationRunner::class);
    $manager = app(TenantDatabaseManager::class);

    $schema = isolationSchema('a');

    createIsolationTenant($runner, $schema);

    $manager->connect($schema);

    $tenantSearchPath = DB::selectOne(
        'SHOW search_path'
    )->search_path;

    expect($tenantSearchPath)
        ->toContain($schema);

    $manager->disconnect();

    $platformSearchPath = DB::selectOne(
        'SHOW search_path'
    )->search_path;

    expect($platformSearchPath)
        ->toContain('public');
});

it('does not retain tenant database state after a failed operation', function (): void {
    $runner = app(TenantMigrationRunner::class);
    $manager = app(TenantDatabaseManager::class);

    $schema = isolationSchema('a');

    createIsolationTenant($runner, $schema);

    try {
        $manager->connect($schema);

        throw new RuntimeException(
            'Simulated tenant operation failure.'
        );
    } catch (RuntimeException) {
        $manager->disconnect();
    }

    expect($manager->isConnected())
        ->toBeFalse();

    expect($manager->activeSchema())
        ->toBeNull();

    expect(
        DB::selectOne('SHOW search_path')->search_path
    )->toContain('public');
});
