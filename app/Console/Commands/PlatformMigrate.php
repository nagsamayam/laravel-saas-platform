<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

#[Signature('platform:migrate {--fresh : Drop all platform tables before migrating} {--seed : Run platform seeders after migration}')]
#[Description('Run platform database migrations')]
class PlatformMigrate extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $path = database_path('migrations/platform');

        $command = $this->option('fresh')
            ? 'migrate:fresh'
            : 'migrate';

        $exitCode = Artisan::call($command, [
            '--path' => $path,
            '--realpath' => true,
            '--force' => true,
        ]);

        $this->output->write(
            Artisan::output()
        );

        if ($exitCode !== self::SUCCESS) {
            return $exitCode;
        }

        if ($this->option('seed')) {
            $exitCode = Artisan::call('db:seed', [
                '--force' => true,
            ]);

            $this->output->write(
                Artisan::output()
            );

            return $exitCode;
        }

        return self::SUCCESS;
    }
}
