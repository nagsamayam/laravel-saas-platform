<?php

declare(strict_types=1);

use App\Enums\TenantMembershipStatus;
use App\Enums\TenantStatus;
use App\Enums\UserStatus;
use App\Models\Platform\Tenant;
use App\Models\Platform\TenantUser;
use App\Models\Platform\User;
use App\Support\Tenancy\TenantContext;
use App\Support\Tenancy\TenantDatabaseManager;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

function middlewareUser(
    string $email = 'user@example.com',
): User {
    return User::query()->create([
        'name' => 'Test User',
        'email' => $email,
        'password' => Hash::make('password'),
        'status' => UserStatus::ACTIVE,
    ]);
}

function middlewareTenant(
    string $slug = 'acme',
): Tenant {
    return Tenant::query()->create([
        'name' => ucfirst($slug),
        'slug' => $slug,
        'status' => TenantStatus::ACTIVE,
    ]);
}

it('establishes tenant context during the request', function (): void {
    $user = middlewareUser();
    $tenant = middlewareTenant();

    TenantUser::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'status' => TenantMembershipStatus::ACTIVE,
    ]);

    Route::middleware(['auth', 'tenant'])
        ->get('/test-tenant-context', function (
            TenantContext $context,
        ): Response {
            expect($context->hasTenant())
                ->toBeTrue();

            expect($context->id())
                ->toBe(request()->header('X-Tenant-ID'));

            expect($context->schema())
                ->toBe('tenant_acme');

            return response('ok');
        });

    $response = $this
        ->actingAs($user)
        ->withHeader('X-Tenant-ID', $tenant->id)
        ->get('/test-tenant-context');

    $response->assertOk();
});

it('resets tenant context after the request', function (): void {
    $user = middlewareUser();
    $tenant = middlewareTenant();

    TenantUser::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'status' => TenantMembershipStatus::ACTIVE,
    ]);

    Route::middleware(['auth', 'tenant'])
        ->get('/test-tenant-cleanup', function (): Response {
            return response('ok');
        });

    $this->actingAs($user)
        ->withHeader('X-Tenant-ID', $tenant->id)
        ->get('/test-tenant-cleanup')
        ->assertOk();

    expect(app(TenantContext::class)->hasTenant())
        ->toBeFalse();

    expect(app(TenantDatabaseManager::class)->isConnected())
        ->toBeFalse();
});

it('resets tenant context when the request throws', function (): void {
    $user = middlewareUser();
    $tenant = middlewareTenant();

    TenantUser::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'status' => TenantMembershipStatus::ACTIVE,
    ]);

    Route::middleware(['auth', 'tenant'])
        ->get('/test-tenant-exception', function (): Response {
            throw new RuntimeException(
                'Simulated application failure.'
            );
        });

    $this->withoutExceptionHandling();

    expect(fn () => $this
        ->actingAs($user)
        ->withHeader('X-Tenant-ID', $tenant->id)
        ->get('/test-tenant-exception'))
        ->toThrow(RuntimeException::class);

    expect(app(TenantContext::class)->hasTenant())
        ->toBeFalse();

    expect(app(TenantDatabaseManager::class)->isConnected())
        ->toBeFalse();
});
