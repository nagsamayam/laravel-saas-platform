<?php

declare(strict_types=1);

namespace App\Models\Platform;

use App\Enums\TenantMembershipStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TenantUser extends Model
{
    use HasUuids;
    use SoftDeletes;

    protected $table = 'tenant_users';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => TenantMembershipStatus::class,
            'deleted_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            Role::class,
            'tenant_user_roles',
            'tenant_user_id',
            'role_id'
        );
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where(
            'status',
            TenantMembershipStatus::ACTIVE->value
        );
    }

    public function isActive(): bool
    {
        return $this->status === TenantMembershipStatus::ACTIVE;
    }
}
