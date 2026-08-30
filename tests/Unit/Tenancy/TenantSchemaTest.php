<?php

declare(strict_types=1);

use App\Support\Tenancy\TenantSchema;
use InvalidArgumentException;

beforeEach(function (): void {
    $this->schema = new TenantSchema;
});

it('generates a schema name from a tenant slug', function (): void {
    expect($this->schema->fromSlug('acme'))
        ->toBe('tenant_acme');
});

it('normalizes a tenant slug', function (): void {
    expect($this->schema->fromSlug('Acme Corporation'))
        ->toBe('tenant_acme_corporation');
});

it('rejects an empty tenant slug', function (): void {
    $this->schema->fromSlug('---');
})->throws(InvalidArgumentException::class);

it('rejects schema names exceeding postgres identifier length', function (): void {
    $this->schema->validate(
        'tenant_'.str_repeat('a', 60)
    );
})->throws(InvalidArgumentException::class);

it('rejects invalid schema names', function (): void {
    $this->schema->validate('tenant-acme');
})->throws(InvalidArgumentException::class);

it('accepts valid schema names', function (): void {
    expect($this->schema->validate('tenant_acme'))
        ->toBe('tenant_acme');
});

it('quotes valid schema identifiers', function (): void {
    expect($this->schema->quote('tenant_acme'))
        ->toBe('"tenant_acme"');
});

it('supports a custom schema prefix', function (): void {
    $schema = new TenantSchema('customer_');

    expect($schema->fromSlug('acme'))
        ->toBe('customer_acme');
});
