<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Enums\TenantStatus;
use App\Models\Platform\Tenant;
use App\Models\Platform\User;
use App\Support\Audit\AuditContext;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuditFieldsTest extends TestCase
{
    protected function tearDown(): void
    {
        AuditContext::clear();

        parent::tearDown();
    }

    public function test_audit_actor_is_recorded_on_create_and_update(): void
    {
        $user = User::query()->create([
            'name' => 'Platform Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'status' => 'active',
        ]);

        AuditContext::setActor($user->id);

        $tenant = Tenant::query()->create([
            'name' => 'Acme',
            'slug' => 'acme',
            'status' => TenantStatus::PENDING,
            'schema_name' => 'tenant_acme',
        ]);

        $this->assertSame(
            $user->id,
            $tenant->created_by
        );

        $this->assertSame(
            $user->id,
            $tenant->updated_by
        );

        $tenant->name = 'Acme Corporation';
        $tenant->save();

        $this->assertSame(
            $user->id,
            $tenant->fresh()->updated_by
        );
    }

    public function test_delete_records_deleted_by(): void
    {
        $user = User::query()->create([
            'name' => 'Platform Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'status' => 'active',
        ]);

        AuditContext::setActor($user->id);

        $tenant = Tenant::query()->create([
            'name' => 'Acme',
            'slug' => 'acme',
            'status' => TenantStatus::PENDING,
            'schema_name' => 'tenant_acme',
        ]);

        $tenant->delete();

        $this->assertSame(
            $user->id,
            Tenant::withTrashed()
                ->findOrFail($tenant->id)
                ->deleted_by
        );
    }
}
