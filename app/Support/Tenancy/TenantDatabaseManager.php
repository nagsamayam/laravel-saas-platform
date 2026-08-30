<?php

declare(strict_types=1);

namespace App\Support\Tenancy;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use LogicException;

final class TenantDatabaseManager
{
    private const CONNECTION = 'pgsql';

    private bool $tenantConnectionActive = false;

    private ?string $activeSchema = null;

    public function connect(string $schema): void
    {
        if ($this->tenantConnectionActive) {
            if ($this->activeSchema === $schema) {
                return;
            }

            throw new LogicException(
                sprintf(
                    'Tenant database connection is already configured for schema [%s].',
                    $this->activeSchema,
                )
            );
        }

        $schema = (new TenantSchema)->validate($schema);

        $connection = $this->connection();

        $connection->statement(
            'SET search_path TO '.$this->quoteSchema($schema).', public'
        );

        $this->tenantConnectionActive = true;
        $this->activeSchema = $schema;
    }

    public function connectToTenant(TenantContext $context): void
    {
        $this->connect($context->schema());
    }

    public function disconnect(): void
    {
        if (! $this->tenantConnectionActive) {
            return;
        }

        $this->connection()->statement(
            'SET search_path TO public'
        );

        $this->tenantConnectionActive = false;
        $this->activeSchema = null;
    }

    public function isConnected(): bool
    {
        return $this->tenantConnectionActive;
    }

    public function activeSchema(): ?string
    {
        return $this->activeSchema;
    }

    public function connection(): Connection
    {
        return DB::connection(self::CONNECTION);
    }

    private function quoteSchema(string $schema): string
    {
        return (new TenantSchema)->quote($schema);
    }
}
