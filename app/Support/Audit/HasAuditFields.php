<?php

declare(strict_types=1);

namespace App\Support\Audit;

use Illuminate\Database\Eloquent\Model;

trait HasAuditFields
{
    protected static function bootHasAuditFields(): void
    {
        static::creating(function (Model $model): void {
            $actorId = AuditContext::actorId();

            if ($actorId !== null) {
                $model->created_by ??= $actorId;
                $model->updated_by ??= $actorId;
            }
        });

        static::updating(function (Model $model): void {
            $actorId = AuditContext::actorId();

            if ($actorId !== null) {
                $model->updated_by = $actorId;
            }
        });

        static::deleting(function (Model $model): void {
            $actorId = AuditContext::actorId();

            if ($actorId !== null) {
                $model->deleted_by = $actorId;
            }
        });

        static::restoring(function (Model $model): void {
            $model->deleted_by = null;

            $actorId = AuditContext::actorId();

            if ($actorId !== null) {
                $model->updated_by = $actorId;
            }
        });
    }

    public function getAuditActorId(): ?string
    {
        return AuditContext::actorId();
    }

    public function getAuditResourceType(): string
    {
        return $this->getMorphClass();
    }

    public function getAuditResourceId(): string
    {
        return (string) $this->getKey();
    }
}
