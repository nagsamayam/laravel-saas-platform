<?php

declare(strict_types=1);

use App\Enums\TenantStatus;
use App\Jobs\Tenancy\ProvisionTenant;
use App\Services\Platform\TenantApprovalService;
use App\Services\Tenancy\TenantProvisioningService;
use App\Support\Tenancy\TenantMigrationRunner;
use Illuminate\Support\Facades\Queue;

it('can execute provisioning after tenant approval', function (): void {
    Queue::fake();

    $tenant = approvalTenant(
        'approval-provisioning',
    );

    $user = approvalUser(
        'approval-provisioning@example.com',
    );

    app(TenantApprovalService::class)
        ->approve($tenant, $user);

    Queue::assertPushed(
        ProvisionTenant::class,
    );

    $job = new ProvisionTenant(
        $tenant->id,
    );

    $job->handle(
        app(TenantProvisioningService::class)
    );

    expect($tenant->fresh()->status)
        ->toBe(TenantStatus::ACTIVE);

    app(TenantMigrationRunner::class)
        ->dropSchema($tenant->schema_name);
});
