<?php

declare(strict_types=1);

namespace App\Support\Concurrency;

interface OptimisticLockable
{
    public function getRowVersion(): int;

    public function incrementRowVersion(): void;
}
