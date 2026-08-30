<?php

declare(strict_types=1);

use App\Enums\TenantStatus;
use App\Models\Platform\Tenant;
use App\Support\Tenancy\TenantContext;
use LogicException;

function makeTenant(
    string $name = 'Acme',
    string $slug = 'acme',
    string $schema = 'tenant_acme',
): Tenant {
    return (new Tenant)
        ->forceFill([
            'name' => $name,
            'slug' => $slug,
            'status' => TenantStatus::ACTIVE,
            'schema_name' => $schema,
        ]);
}

it('requires a tenant before accessing the context', function (): void {
    $context = new TenantContext;

    expect($context->hasTenant())
        ->toBeFalse();

    expect(fn () => $context->get())
        ->toThrow(LogicException::class);
});

it('can hold a tenant', function (): void {
    $tenant = makeTenant();

    $context = new TenantContext;

    $context->set($tenant);

    expect($context->hasTenant())
        ->toBeTrue();

    expect($context->get())
        ->toBe($tenant);

    expect($context->id())
        ->toBe((string) $tenant->getKey());

    expect($context->schema())
        ->toBe('tenant_acme');
});

it('can be cleared', function (): void {
    $context = new TenantContext;

    $context->set(makeTenant());

    $context->clear();

    expect($context->hasTenant())
        ->toBeFalse();
});

it('cannot be set twice', function (): void {
    $context = new TenantContext;

    $context->set(
        makeTenant()
    );

    expect(fn () => $context->set(
        makeTenant(
            name: 'Foo',
            slug: 'foo',
            schema: 'tenant_foo',
        )
    ))->toThrow(LogicException::class);
});
