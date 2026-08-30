<?php

declare(strict_types=1);

namespace Tests\Unit\Tenancy;

use App\Enums\TenantStatus;
use App\Models\Platform\Tenant;
use App\Support\Tenancy\TenantContext;
use LogicException;
use Tests\TestCase;

class TenantContextTest extends TestCase
{
    public function test_context_requires_a_tenant(): void
    {
        $context = new TenantContext;

        $this->assertFalse(
            $context->hasTenant()
        );

        $this->expectException(LogicException::class);

        $context->get();
    }

    public function test_context_can_hold_a_tenant(): void
    {
        $tenant = new Tenant([
            'name' => 'Acme',
            'slug' => 'acme',
            'status' => TenantStatus::ACTIVE,
            'schema_name' => 'tenant_acme',
        ]);

        $context = new TenantContext;

        $context->set($tenant);

        $this->assertTrue(
            $context->hasTenant()
        );

        $this->assertSame(
            $tenant,
            $context->get()
        );

        $this->assertSame(
            'tenant_acme',
            $context->schema()
        );
    }

    public function test_context_can_be_cleared(): void
    {
        $tenant = new Tenant([
            'name' => 'Acme',
            'slug' => 'acme',
            'status' => TenantStatus::ACTIVE,
            'schema_name' => 'tenant_acme',
        ]);

        $context = new TenantContext;

        $context->set($tenant);
        $context->clear();

        $this->assertFalse(
            $context->hasTenant()
        );
    }

    public function test_context_cannot_be_set_twice(): void
    {
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

        $this->expectException(LogicException::class);

        $context->set($second);
    }
}
