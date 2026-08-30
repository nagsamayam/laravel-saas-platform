<?php

declare(strict_types=1);

namespace App\Models\Platform;

use App\Enums\TenantStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends Model
{
    use HasUuids;
    use SoftDeletes;

    protected $table = 'tenants';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'name',
        'slug',
        'status',
        'schema_name',
    ];

    protected function casts(): array
    {
        return [
            'status' => TenantStatus::class,
            'approved_at' => 'datetime',
            'provisioning_started_at' => 'datetime',
            'provisioned_at' => 'datetime',
            'suspended_at' => 'datetime',
            'deleted_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
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
        )->wherePivotNull('deleted_at');
    }
}
