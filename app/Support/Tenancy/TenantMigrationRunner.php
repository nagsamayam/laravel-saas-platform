<?php

declare(strict_types=1);

namespace App\Support\Tenancy;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class TenantMigrationRunner implements TenantMigrationRunnerContract
{
    private const CONNECTION = 'pgsql';

    private const MIGRATION_PATH = 'database/migrations/tenant';

    public function createSchema(string $schema): void
    {
        $schema = (new TenantSchema)->validate($schema);

        $connection = $this->connection();

        $connection->statement(
            'CREATE SCHEMA IF NOT EXISTS '
                .(new TenantSchema)->quote($schema)
        );
    }

    public function dropSchema(string $schema): void
    {
        $schema = (new TenantSchema)->validate($schema);

        $this->connection()->statement(
            'DROP SCHEMA IF EXISTS '
                .(new TenantSchema)->quote($schema)
                .' CASCADE'
        );
    }

    public function run(string $schema): void
    {
        $schema = (new TenantSchema)->validate($schema);

        $this->createSchema($schema);

        $connection = $this->connection();

        $this->setSearchPath(
            $connection,
            $schema
        );

        try {
            $exitCode = Artisan::call('migrate', [
                '--database' => self::CONNECTION,
                '--path' => database_path('migrations/tenant'),
                '--realpath' => true,
                '--force' => true,
            ]);

            if ($exitCode !== 0) {
                throw new RuntimeException(
                    sprintf(
                        'Tenant migrations failed for schema [%s].%s',
                        $schema,
                        PHP_EOL.Artisan::output()
                    )
                );
            }
        } finally {
            $this->resetSearchPath($connection);
        }
    }

    public function hasMigrationTable(string $schema): bool
    {
        $schema = (new TenantSchema)->validate($schema);

        return (bool) $this->connection()
            ->selectOne(
                'SELECT EXISTS (
                    SELECT 1
                    FROM information_schema.tables
                    WHERE table_schema = ?
                    AND table_name = ?
                ) AS exists',
                [
                    $schema,
                    'migrations',
                ]
            )
            ->exists;
    }

    private function setSearchPath(
        Connection $connection,
        string $schema,
    ): void {
        $connection->statement(
            'SET search_path TO '
                .(new TenantSchema)->quote($schema)
                .', public'
        );
    }

    private function resetSearchPath(
        Connection $connection,
    ): void {
        $connection->statement(
            'SET search_path TO public'
        );
    }

    private function connection(): Connection
    {
        return DB::connection(self::CONNECTION);
    }
}
