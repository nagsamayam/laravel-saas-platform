<?php

declare(strict_types=1);

use App\Support\Tenancy\TenantDatabaseManager;
use Illuminate\Support\Facades\DB;
use LogicException;

it('sets the tenant schema as the postgres search path', function (): void {
    $manager = app(TenantDatabaseManager::class);

    $manager->connect('tenant_acme');

    $searchPath = DB::selectOne(
        'SHOW search_path'
    )->search_path;

    expect($searchPath)
        ->toContain('tenant_acme')
        ->toContain('public');

    $manager->disconnect();
});

it('returns the active schema', function (): void {
    $manager = app(TenantDatabaseManager::class);

    $manager->connect('tenant_acme');

    expect($manager->isConnected())
        ->toBeTrue();

    expect($manager->activeSchema())
        ->toBe('tenant_acme');

    $manager->disconnect();
});

it('restores the public schema when disconnected', function (): void {
    $manager = app(TenantDatabaseManager::class);

    $manager->connect('tenant_acme');

    $manager->disconnect();

    $searchPath = DB::selectOne(
        'SHOW search_path'
    )->search_path;

    expect($searchPath)
        ->toContain('public');

    expect($manager->isConnected())
        ->toBeFalse();

    expect($manager->activeSchema())
        ->toBeNull();
});

it('does not allow switching tenants without disconnecting', function (): void {
    $manager = app(TenantDatabaseManager::class);

    $manager->connect('tenant_acme');

    expect(fn () => $manager->connect('tenant_foo'))
        ->toThrow(LogicException::class);

    $manager->disconnect();
});

it('allows reconnecting after disconnect', function (): void {
    $manager = app(TenantDatabaseManager::class);

    $manager->connect('tenant_acme');
    $manager->disconnect();

    $manager->connect('tenant_foo');

    expect($manager->activeSchema())
        ->toBe('tenant_foo');

    $manager->disconnect();
});

it('rejects an invalid schema name', function (): void {
    $manager = app(TenantDatabaseManager::class);

    $manager->connect('tenant-acme');
})->throws(InvalidArgumentException::class);
