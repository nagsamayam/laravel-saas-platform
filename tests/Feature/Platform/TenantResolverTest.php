<?php

declare(strict_types=1);

use App\Enums\RoleType;
use App\Enums\TenantMembershipStatus;
use App\Enums\TenantStatus;
use App\Enums\UserStatus;
use App\Models\Platform\Role;
use App\Models\Platform\Tenant;
use App\Models\Platform\TenantUser;
use App\Models\Platform\User;
use App\Support\Tenancy\TenantResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

function resolverUser(
    string $email = 'user@example.com',
): User {
    return User::query()->create([
        'name' => 'Test User',
        'email' => $email,
        'password' => Hash::make('password'),
        'status' => UserStatus::ACTIVE,
    ]);
}

function resolverTenant(
    string $slug = 'acme',
    TenantStatus $status = TenantStatus::ACTIVE,
): Tenant {
    return Tenant::query()->create([
        'name' => ucfirst($slug),
        'slug' => $slug,
        'status' => $status,
    ]);
}

function resolverRequest(string $tenantId): Request
{
    return Request::create(
        '/api/test',
        'GET',
        [],
        [],
        [],
        [
            'HTTP_X_TENANT_ID' => $tenantId,
        ],
    );
}

it('resolves an active tenant for an active member', function (): void {
    $user = resolverUser();
    $tenant = resolverTenant();

    TenantUser::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'status' => TenantMembershipStatus::ACTIVE,
    ]);

    $this->actingAs($user);

    $resolved = app(TenantResolver::class)
        ->resolve(resolverRequest($tenant->id));

    expect($resolved->id)
        ->toBe($tenant->id);
});

it('rejects a user without tenant membership', function (): void {
    $user = resolverUser();
    $tenant = resolverTenant();

    $this->actingAs($user);

    app(TenantResolver::class)
        ->resolve(resolverRequest($tenant->id));
})->throws(AccessDeniedHttpException::class);

it('rejects inactive tenant membership', function (): void {
    $user = resolverUser();
    $tenant = resolverTenant();

    TenantUser::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'status' => TenantMembershipStatus::SUSPENDED,
    ]);

    $this->actingAs($user);

    app(TenantResolver::class)
        ->resolve(resolverRequest($tenant->id));
})->throws(AccessDeniedHttpException::class);

it('rejects an inactive tenant', function (): void {
    $user = resolverUser();

    $tenant = resolverTenant(
        status: TenantStatus::SUSPENDED,
    );

    $this->actingAs($user);

    app(TenantResolver::class)
        ->resolve(resolverRequest($tenant->id));
})->throws(NotFoundHttpException::class);

it('rejects a missing tenant header', function (): void {
    $user = resolverUser();

    $this->actingAs($user);

    $request = Request::create(
        '/api/test',
        'GET',
    );

    app(TenantResolver::class)
        ->resolve($request);
})->throws(BadRequestHttpException::class);

it('rejects an invalid tenant UUID', function (): void {
    $user = resolverUser();

    $this->actingAs($user);

    app(TenantResolver::class)
        ->resolve(
            resolverRequest('not-a-uuid'),
        );
})->throws(BadRequestHttpException::class);

it('allows a platform administrator to access an active tenant', function (): void {
    $user = resolverUser(
        'admin@example.com',
    );

    $role = Role::query()->create([
        'name' => 'Platform Administrator',
        'slug' => 'platform_admin',
        'type' => RoleType::PLATFORM,
        'is_system' => true,
    ]);

    $user->platformRoles()->attach($role);

    $tenant = resolverTenant();

    $this->actingAs($user);

    $resolved = app(TenantResolver::class)
        ->resolve(resolverRequest($tenant->id));

    expect($resolved->id)
        ->toBe($tenant->id);
});

it('prevents a tenant user from resolving another tenant', function (): void {
    $user = resolverUser(
        'tenant-a@example.com',
    );

    $tenantA = resolverTenant('tenant-a');
    $tenantB = resolverTenant('tenant-b');

    TenantUser::query()->create([
        'tenant_id' => $tenantA->id,
        'user_id' => $user->id,
        'status' => TenantMembershipStatus::ACTIVE,
    ]);

    $this->actingAs($user);

    app(TenantResolver::class)
        ->resolve(
            resolverRequest($tenantB->id)
        );
})->throws(AccessDeniedHttpException::class);
