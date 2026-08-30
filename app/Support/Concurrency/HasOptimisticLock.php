<?php

declare(strict_types=1);

namespace App\Support\Concurrency;

use Illuminate\Database\Eloquent\Builder;

trait HasOptimisticLock
{
    public function getRowVersion(): int
    {
        return (int) ($this->getAttribute('row_version') ?? 1);
    }

    public function incrementRowVersion(): void
    {
        $this->setAttribute(
            'row_version',
            $this->getRowVersion() + 1
        );
    }

    protected function performUpdate(Builder $query): bool
    {
        if ($this->fireModelEvent('updating') === false) {
            return false;
        }

        if ($this->usesTimestamps()) {
            $this->updateTimestamps();
        }

        $expectedVersion = (int) (
            $this->getRawOriginal('row_version') ?? 1
        );

        $this->setAttribute(
            'row_version',
            $expectedVersion + 1
        );

        $dirty = $this->getDirtyForUpdate();

        if ($dirty === []) {
            return true;
        }

        $updated = $query
            ->where(
                $this->getKeyName(),
                $this->getKey()
            )
            ->where(
                'row_version',
                $expectedVersion
            )
            ->update($dirty);

        if ($updated === 0) {
            throw new OptimisticLockException(
                static::class,
                (string) $this->getKey(),
                $expectedVersion
            );
        }

        $this->syncChanges();

        $this->syncOriginalAttribute(
            'row_version',
            $this->getAttribute('row_version')
        );

        $this->fireModelEvent('updated', false);

        return true;
    }
}
