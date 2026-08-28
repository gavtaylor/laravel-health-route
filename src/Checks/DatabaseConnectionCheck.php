<?php

declare(strict_types=1);

namespace GavTaylor\HealthRoute\Checks;

use GavTaylor\HealthRoute\Checks\Contracts\Check;
use Illuminate\Support\Facades\DB;
use Throwable;

final class DatabaseConnectionCheck implements Check
{
    public function name(): string
    {
        return 'database';
    }

    public function run(): CheckResult
    {
        $connectionName = config('health-route.checks_config.database.connection');

        try {
            DB::connection($connectionName)->select('select 1');

            return CheckResult::up($this->name());
        } catch (Throwable) {
            return CheckResult::down($this->name(), 'Could not connect to the database.');
        }
    }
}
