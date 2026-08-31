<?php

declare(strict_types=1);

use App\Enums\TenantStatus;
use App\Enums\UserStatus;
use App\Jobs\Tenancy\ProvisionTenant;
use App\Models\Platform\Tenant;
use App\Models\Platform\User;
use App\Services\Platform\TenantApprovalService;
use App\Support\Concurrency\OptimisticLockException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use RuntimeException;

function approvalUser(
    string $email = 'approver@example.com',
): User {
    return User::query()->create([
        'name' => 'Platform Approver',
        'email' => $email,
        'password' => Hash::make('password'),
        'status' => UserStatus::ACTIVE,
    ]);
}

function approvalTenant(
    string $slug = 'approval-tenant',
    TenantStatus $status = TenantStatus::PENDING,
): Tenant {
    return Tenant::query()->create([
        'name' => 'Approval Tenant',
        'slug' => $slug,
        'status' => $status,
    ]);
}

it('approves a pending tenant', function (): void {
    Queue::fake();

    $tenant = approvalTenant();
    $user = approvalUser();

    $result = app(TenantApprovalService::class)
        ->approve($tenant, $user);

    expect($result->status)
        ->toBe(TenantStatus::APPROVED);

    expect($result->updated_by)
        ->toBe($user->id);

    Queue::assertPushed(
        ProvisionTenant::class,
    );
});

it('is idempotent when the tenant is already approved', function (): void {
    $tenant = approvalTenant(
        'already-approved',
        TenantStatus::APPROVED,
    );

    $originalApprovedAt = now()->subMinute();

    $tenant->forceFill([
        'approved_at' => $originalApprovedAt,
    ])->save();

    $tenant = $tenant->fresh();

    $user = approvalUser();

    $result = app(TenantApprovalService::class)
        ->approve($tenant, $user);

    expect($result->status)
        ->toBe(TenantStatus::APPROVED);

    expect(
        $result->approved_at->equalTo(
            $tenant->approved_at
        )
    )->toBeTrue();
});

it('does not change an active tenant', function (): void {
    $tenant = approvalTenant(
        'already-active',
        TenantStatus::ACTIVE,
    );

    $user = approvalUser();

    $result = app(TenantApprovalService::class)
        ->approve($tenant, $user);

    expect($result->status)
        ->toBe(TenantStatus::ACTIVE);
});

it('rejects a suspended tenant', function (): void {
    $tenant = approvalTenant(
        'suspended',
        TenantStatus::SUSPENDED,
    );

    $user = approvalUser();

    app(TenantApprovalService::class)
        ->approve($tenant, $user);
})->throws(RuntimeException::class);

it('rejects a provisioning tenant', function (): void {
    $tenant = approvalTenant(
        'provisioning',
        TenantStatus::PROVISIONING,
    );

    $user = approvalUser();

    app(TenantApprovalService::class)
        ->approve($tenant, $user);
})->throws(RuntimeException::class);

it('rejects a provisioning failed tenant', function (): void {
    $tenant = approvalTenant(
        'failed',
        TenantStatus::PROVISIONING_FAILED,
    );

    $user = approvalUser();

    app(TenantApprovalService::class)
        ->approve($tenant, $user);
})->throws(RuntimeException::class);

it('protects approval from stale concurrent updates', function (): void {
    Queue::fake();

    $tenant = approvalTenant(
        'concurrent-approval',
    );

    $first = Tenant::query()->findOrFail(
        $tenant->id
    );

    $second = Tenant::query()->findOrFail(
        $tenant->id
    );

    $firstApprover = approvalUser(
        'approver-one@example.com',
    );

    $secondApprover = approvalUser(
        'approver-two@example.com',
    );

    $service = app(TenantApprovalService::class);

    $service->approve(
        $first,
        $firstApprover,
    );

    expect($first->fresh()->status)
        ->toBe(TenantStatus::APPROVED);

    expect(fn () => $service->approve(
        $second,
        $secondApprover,
    ))->toThrow(
        OptimisticLockException::class
    );

    Queue::assertPushed(
        ProvisionTenant::class,
        1,
    );
});

it('dispatches tenant provisioning after approval', function (): void {
    Queue::fake();

    $tenant = approvalTenant(
        'dispatch-approval',
    );

    $user = approvalUser(
        'dispatch-approver@example.com',
    );

    $result = app(TenantApprovalService::class)
        ->approve($tenant, $user);

    expect($result->status)
        ->toBe(TenantStatus::APPROVED);

    Queue::assertPushed(
        ProvisionTenant::class,
        function (ProvisionTenant $job) use ($tenant): bool {
            return $job->tenantId === $tenant->id;
        },
    );
});

it('does not dispatch provisioning for an already approved tenant', function (): void {
    Queue::fake();

    $tenant = approvalTenant(
        'already-approved-dispatch',
        TenantStatus::APPROVED,
    );

    $user = approvalUser(
        'already-approved-dispatcher@example.com',
    );

    app(TenantApprovalService::class)
        ->approve($tenant, $user);

    Queue::assertNotPushed(
        ProvisionTenant::class,
    );
});

it('does not dispatch provisioning for an active tenant', function (): void {
    Queue::fake();

    $tenant = approvalTenant(
        'active-no-dispatch',
        TenantStatus::ACTIVE,
    );

    $user = approvalUser(
        'active-no-dispatcher@example.com',
    );

    app(TenantApprovalService::class)
        ->approve($tenant, $user);

    Queue::assertNotPushed(
        ProvisionTenant::class,
    );
});
