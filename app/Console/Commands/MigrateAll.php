<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MigrateAll extends Command
{
    /**
     * Two databases need two migration runs.
     *
     * Laravel records what has run in a repository that belongs to the connection the
     * run was invoked with, not to the individual migration. One run therefore cannot
     * keep two separate histories.
     */
    protected $signature = 'migrate:all {--force : Run without confirmation, as the deploy script does}';

    protected $description = 'Run migrations for the primary and audit databases';

    public function handle(): int
    {
        $force = (bool) $this->option('force');

        $exitCode = $this->call('migrate', [
            '--force' => $force,
        ]);

        if ($exitCode !== self::SUCCESS) {
            $this->error('Primary migrations failed. The audit database was left untouched.');

            return $exitCode;
        }

        return $this->call('migrate', [
            '--database' => 'audit',
            '--path' => 'database/migrations/audit',
            '--force' => $force,
        ]);
    }
}
