<?php

declare(strict_types=1);

use App\Support\Tenancy\TenantMigrationRunner;
use Illuminate\Support\Facades\DB;

function migrationTestSchema(string $suffix): string
{
    return 'tenant_test_'.$suffix;
}

it('creates a tenant schema', function (): void {
    $schema = migrationTestSchema('create');

    $runner = app(TenantMigrationRunner::class);

    $runner->createSchema($schema);

    $exists = DB::selectOne(
        'SELECT EXISTS (
            SELECT 1
            FROM information_schema.schemata
            WHERE schema_name = ?
        ) AS exists',
        [$schema]
    );

    expect((bool) $exists->exists)
        ->toBeTrue();

    $runner->dropSchema($schema);
});

it('runs tenant migrations inside the tenant schema', function (): void {
    $schema = migrationTestSchema('migration');

    $runner = app(TenantMigrationRunner::class);

    $runner->run($schema);

    $projectsExists = DB::selectOne(
        'SELECT EXISTS (
            SELECT 1
            FROM information_schema.tables
            WHERE table_schema = ?
            AND table_name = ?
        ) AS exists',
        [
            $schema,
            'projects',
        ]
    );

    $migrationsExists = DB::selectOne(
        'SELECT EXISTS (
            SELECT 1
            FROM information_schema.tables
            WHERE table_schema = ?
            AND table_name = ?
        ) AS exists',
        [
            $schema,
            'migrations',
        ]
    );

    expect((bool) $projectsExists->exists)
        ->toBeTrue();

    expect((bool) $migrationsExists->exists)
        ->toBeTrue();

    $runner->dropSchema($schema);
});

it('keeps tenant migration history isolated', function (): void {
    $schemaA = migrationTestSchema('a');
    $schemaB = migrationTestSchema('b');

    $runner = app(TenantMigrationRunner::class);

    $runner->run($schemaA);
    $runner->run($schemaB);

    $countA = DB::selectOne(
        'SELECT COUNT(*) AS count
         FROM "'.$schemaA.'"."migrations"'
    );

    $countB = DB::selectOne(
        'SELECT COUNT(*) AS count
         FROM "'.$schemaB.'"."migrations"'
    );

    expect((int) $countA->count)
        ->toBeGreaterThan(0);

    expect((int) $countB->count)
        ->toBeGreaterThan(0);

    $runner->dropSchema($schemaA);
    $runner->dropSchema($schemaB);
});

it('resets search path after migration', function (): void {
    $schema = migrationTestSchema('reset');

    $runner = app(TenantMigrationRunner::class);

    $runner->run($schema);

    $searchPath = DB::selectOne(
        'SHOW search_path'
    )->search_path;

    expect($searchPath)
        ->toContain('public');

    $runner->dropSchema($schema);
});

it('can run tenant migrations repeatedly', function (): void {
    $schema = migrationTestSchema('repeat');

    $runner = app(TenantMigrationRunner::class);

    $runner->run($schema);
    $runner->run($schema);

    expect($runner->hasMigrationTable($schema))
        ->toBeTrue();

    $runner->dropSchema($schema);
});
