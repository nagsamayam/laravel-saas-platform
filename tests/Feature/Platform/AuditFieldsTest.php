<?php

declare(strict_types=1);

use App\Enums\TenantStatus;
use App\Models\Platform\Tenant;
use App\Models\Platform\User;
use App\Support\Audit\AuditContext;
use Illuminate\Support\Facades\Hash;

afterEach(function () {
    AuditContext::clear();
});

test('audit actor is recorded on create and update', function () {
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

    expect($tenant->created_by)->toBe($user->id)
        ->and($tenant->updated_by)->toBe($user->id);

    $tenant->name = 'Acme Corporation';
    $tenant->save();

    expect($tenant->fresh()->updated_by)->toBe($user->id);
});

test('delete records deleted by', function () {
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

    expect(Tenant::withTrashed()->findOrFail($tenant->id)->deleted_by)->toBe($user->id);
});
