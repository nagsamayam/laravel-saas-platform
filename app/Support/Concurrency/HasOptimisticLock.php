<?php

declare(strict_types=1);

namespace App\Support\Concurrency;

use Illuminate\Database\Eloquent\Builder;

trait HasOptimisticLock
{
    public function getRowVersion(): int
    {
        return (int) $this->getAttribute('row_version');
    }

    public function incrementRowVersion(): void
    {
        $this->setAttribute(
            'row_version',
            $this->getRowVersion() + 1
        );
    }

    protected function performUpdate(
        Builder $query
    ): bool {
        if ($this->fireModelEvent('updating') === false) {
            return false;
        }

        if ($this->usesTimestamps()) {
            $this->updateTimestamps();
        }

        $dirty = $this->getDirtyForUpdate();

        if (! $this->isDirty('row_version')) {
            $this->incrementRowVersion();
            $dirty = $this->getDirtyForUpdate();
        }

        if ($dirty === []) {
            return true;
        }

        $expectedVersion = $this->getOriginal('row_version');

        $query->where(
            $this->getKeyName(),
            $this->getKey()
        )->where(
            'row_version',
            $expectedVersion
        );

        $dirty['row_version'] = $this->getRowVersion();

        $updated = $query->update($dirty);

        if ($updated === 0) {
            throw new OptimisticLockException(
                static::class,
                (string) $this->getKey(),
                (int) $expectedVersion
            );
        }

        $this->syncChanges();

        $this->fireModelEvent('updated', false);

        return true;
    }
}
