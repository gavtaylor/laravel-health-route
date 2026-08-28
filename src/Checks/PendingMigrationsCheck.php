<?php

declare(strict_types=1);

namespace GavTaylor\HealthRoute\Checks;

use GavTaylor\HealthRoute\Checks\Contracts\Check;
use Illuminate\Database\Migrations\Migrator;
use Throwable;

final class PendingMigrationsCheck implements Check
{
    public function __construct(
        private readonly Migrator $migrator,
    ) {
        //
    }

    public function name(): string
    {
        return 'pending-migrations';
    }

    public function run(): CheckResult
    {
        try {
            if (! $this->migrator->repositoryExists()) {
                return CheckResult::degraded($this->name(), 'The migrations table does not exist yet.');
            }

            $files = $this->migrator->getMigrationFiles($this->migrator->paths() ?: [database_path('migrations')]);
            $ran = $this->migrator->getRepository()->getRan();

            $pending = array_diff(array_keys($files), $ran);

            if ($pending !== []) {
                return CheckResult::degraded(
                    $this->name(),
                    sprintf('%d pending migration(s).', count($pending)),
                    ['pending' => array_values($pending)],
                );
            }

            return CheckResult::up($this->name());
        } catch (Throwable) {
            return CheckResult::down($this->name(), 'Could not determine migration status.');
        }
    }
}
