<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->runPlatformMigrations();
    }

    private function runPlatformMigrations(): void
    {
        Artisan::call('migrate:fresh', [
            '--path' => database_path('migrations/platform'),
            '--realpath' => true,
            '--force' => true,
        ]);
    }
}
