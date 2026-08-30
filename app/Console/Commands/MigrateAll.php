<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MigrateAll extends Command
{
    /**
     * Two databases means two migration runs: the repository that records what ran
     * belongs to the connection the run was invoked with, not to the individual
     * migration, so a single run cannot keep two histories.
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
