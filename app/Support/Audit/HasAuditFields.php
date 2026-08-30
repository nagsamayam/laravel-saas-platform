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

            if ($model->getAttribute('row_version') === null) {
                $model->setAttribute('row_version', 1);
            }

            if ($actorId !== null) {
                $model->setAttribute(
                    'created_by',
                    $model->getAttribute('created_by') ?? $actorId
                );

                $model->setAttribute(
                    'updated_by',
                    $model->getAttribute('updated_by') ?? $actorId
                );
            }
        });

        static::updating(function (Model $model): void {
            $actorId = AuditContext::actorId();

            if ($actorId !== null) {
                $model->setAttribute('updated_by', $actorId);
            }
        });

        static::deleting(function (Model $model): void {
            $actorId = AuditContext::actorId();

            if ($actorId !== null) {
                $model->setAttribute('deleted_by', $actorId);

                /*
                 * SoftDeletes performs its own database update after
                 * this event. Persist deleted_by before that happens.
                 */
                $model->saveQuietly();
            }
        });

        static::restoring(function (Model $model): void {
            $model->setAttribute('deleted_by', null);

            $actorId = AuditContext::actorId();

            if ($actorId !== null) {
                $model->setAttribute('updated_by', $actorId);
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
