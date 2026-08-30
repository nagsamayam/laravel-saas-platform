<?php

declare(strict_types=1);

namespace App\Models\Platform;

use App\Enums\RoleType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Role extends Model
{
    use HasUuids;
    use SoftDeletes;

    protected $table = 'roles';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'name',
        'slug',
        'type',
        'description',
        'is_system',
    ];

    protected function casts(): array
    {
        return [
            'type' => RoleType::class,
            'is_system' => 'boolean',
            'deleted_at' => 'datetime',
        ];
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            Permission::class,
            'role_permissions',
            'role_id',
            'permission_id'
        );
    }

    public function platformUsers(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'user_roles',
            'role_id',
            'user_id'
        );
    }

    public function tenantUsers(): BelongsToMany
    {
        return $this->belongsToMany(
            TenantUser::class,
            'tenant_user_roles',
            'role_id',
            'tenant_user_id'
        );
    }

    public function scopePlatform(Builder $query): Builder
    {
        return $query->where('type', RoleType::PLATFORM->value);
    }

    public function scopeTenant(Builder $query): Builder
    {
        return $query->where('type', RoleType::TENANT->value);
    }

    public function hasPermission(string $permission): bool
    {
        return $this->permissions
            ->contains('name', $permission);
    }
}
