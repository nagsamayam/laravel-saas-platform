<?php

declare(strict_types=1);

namespace App\Models\Platform;

use App\Enums\TenantStatus;
use App\Support\Audit\HasAuditFields;
use App\Support\Concurrency\HasOptimisticLock;
use App\Support\Concurrency\OptimisticLockable;
use App\Support\Tenancy\TenantSchema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends Model implements OptimisticLockable
{
    use HasAuditFields;
    use HasFactory;
    use HasOptimisticLock;
    use HasUuids;
    use SoftDeletes;

    protected $table = 'tenants';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'name',
        'slug',
        'status',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $tenant): void {
            if ($tenant->schema_name === null) {
                $tenant->schema_name = app(TenantSchema::class)
                    ->fromSlug($tenant->slug);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'status' => TenantStatus::class,
            'approved_at' => 'datetime',
            'provisioning_started_at' => 'datetime',
            'provisioned_at' => 'datetime',
            'suspended_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(TenantUser::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'tenant_users',
            'tenant_id',
            'user_id'
        )
            ->wherePivotNull('deleted_at')
            ->withPivot([
                'id',
                'status',
            ]);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where(
            'status',
            TenantStatus::ACTIVE->value
        );
    }

    public function scopeProvisionable(Builder $query): Builder
    {
        return $query->whereIn('status', [
            TenantStatus::APPROVED->value,
            TenantStatus::PROVISIONING_FAILED->value,
        ]);
    }
}
