<?php

declare(strict_types=1);

namespace App\Models\Platform;

use App\Enums\RoleType;
use App\Enums\UserStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory;
    use HasUuids;
    use Notifiable;
    use SoftDeletes;

    protected $table = 'users';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'name',
        'email',
        'password',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'status' => UserStatus::class,
            'email_verified_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function tenantMemberships(): HasMany
    {
        return $this->hasMany(TenantUser::class, 'user_id');
    }

    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(
            Tenant::class,
            'tenant_users',
            'user_id',
            'tenant_id'
        )
            ->wherePivotNull('deleted_at')
            ->withPivot([
                'id',
                'status',
            ]);
    }

    public function platformRoles(): BelongsToMany
    {
        return $this->belongsToMany(
            Role::class,
            'user_roles',
            'user_id',
            'role_id'
        );
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', UserStatus::ACTIVE->value);
    }

    public function isActive(): bool
    {
        return $this->status === UserStatus::ACTIVE;
    }

    public function isPlatformAdmin(): bool
    {
        return $this->platformRoles()
            ->where('slug', 'platform_admin')
            ->where('type', RoleType::PLATFORM)
            ->exists();
    }

    public function canManagePlatform(): bool
    {
        return $this->isPlatformAdmin();
    }
}
