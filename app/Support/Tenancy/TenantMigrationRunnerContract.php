<?php

declare(strict_types=1);

namespace App\Support\Tenancy;

interface TenantMigrationRunnerContract
{
    public function createSchema(string $schema): void;

    public function dropSchema(string $schema): void;

    public function run(string $schema): void;

    public function hasMigrationTable(string $schema): bool;
}
