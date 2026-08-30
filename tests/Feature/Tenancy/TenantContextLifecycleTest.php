<?php

declare(strict_types=1);

use App\Enums\TenantMembershipStatus;
use App\Enums\TenantStatus;
use App\Enums\UserStatus;
use App\Models\Platform\Tenant;
use App\Models\Platform\TenantUser;
use App\Models\Platform\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

it('clears tenant context after request completion', function (): void {
    $user = User::query()->create([
        'name' => 'Context User',
        'email' => 'context@example.com',
        'password' => Hash::make('password'),
        'status' => UserStatus::ACTIVE,
    ]);

    $tenant = Tenant::query()->create([
        'name' => 'Context Tenant',
        'slug' => 'context-tenant',
        'status' => TenantStatus::ACTIVE,
    ]);

    TenantUser::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'status' => TenantMembershipStatus::ACTIVE,
    ]);

    Route::middleware(['auth', 'tenant'])
        ->get('/test-context-lifecycle', function (): Response {
            expect(app(TenantContext::class)->hasTenant())
                ->toBeTrue();

            return response('ok');
        });

    $this->actingAs($user)
        ->withHeader('X-Tenant-ID', $tenant->id)
        ->get('/test-context-lifecycle')
        ->assertOk();

    expect(app(TenantContext::class)->hasTenant())
        ->toBeFalse();
});
