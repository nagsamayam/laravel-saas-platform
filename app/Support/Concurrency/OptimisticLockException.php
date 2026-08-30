<?php

declare(strict_types=1);

namespace App\Support\Concurrency;

use RuntimeException;

class OptimisticLockException extends RuntimeException
{
    public function __construct(
        string $model,
        string|int $id,
        int $expectedVersion,
    ) {
        parent::__construct(
            sprintf(
                'Optimistic concurrency conflict for %s [%s]. Expected row_version %d.',
                $model,
                $id,
                $expectedVersion,
            )
        );
    }
}
