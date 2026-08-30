<?php

declare(strict_types=1);

use App\Enums\TenantStatus;
use App\Models\Platform\Tenant;
use App\Support\Tenancy\TenantContext;

test('context requires a tenant', function () {
    $context = new TenantContext;

    expect($context->hasTenant())->toBeFalse();

    $context->get();
})->throws(LogicException::class);

test('context can hold a tenant', function () {
    $tenant = new Tenant([
        'name' => 'Acme',
        'slug' => 'acme',
        'status' => TenantStatus::ACTIVE,
        'schema_name' => 'tenant_acme',
    ]);

    $context = new TenantContext;

    $context->set($tenant);

    expect($context->hasTenant())->toBeTrue()
        ->and($context->get())->toBe($tenant)
        ->and($context->schema())->toBe('tenant_acme');
});

test('context can be cleared', function () {
    $tenant = new Tenant([
        'name' => 'Acme',
        'slug' => 'acme',
        'status' => TenantStatus::ACTIVE,
        'schema_name' => 'tenant_acme',
    ]);

    $context = new TenantContext;

    $context->set($tenant);
    $context->clear();

    expect($context->hasTenant())->toBeFalse();
});

test('context cannot be set twice', function () {
    $first = new Tenant([
        'name' => 'Acme',
        'slug' => 'acme',
        'status' => TenantStatus::ACTIVE,
        'schema_name' => 'tenant_acme',
    ]);

    $second = new Tenant([
        'name' => 'Foo',
        'slug' => 'foo',
        'status' => TenantStatus::ACTIVE,
        'schema_name' => 'tenant_foo',
    ]);

    $context = new TenantContext;

    $context->set($first);

    $context->set($second);
})->throws(LogicException::class);
