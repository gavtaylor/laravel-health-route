<?php

declare(strict_types=1);

namespace GavTaylor\HealthRoute\Checks;

use GavTaylor\HealthRoute\Checks\Contracts\Check;

final class DiskSpaceCheck implements Check
{
    public function name(): string
    {
        return 'disk';
    }

    public function run(): CheckResult
    {
        $path = (string) config('health-route.checks_config.disk.path', storage_path());

        $free = @disk_free_space($path);
        $total = @disk_total_space($path);

        if ($free === false || $total === false || $total <= 0.0) {
            return CheckResult::down($this->name(), 'Could not determine free disk space.');
        }

        $percentFree = round(($free / $total) * 100, 1);

        $downBelow = (float) config('health-route.checks_config.disk.down_below_percent', 5);
        $degradedBelow = (float) config('health-route.checks_config.disk.degraded_below_percent', 15);

        $context = ['percent_free' => $percentFree];

        if ($percentFree < $downBelow) {
            return CheckResult::down($this->name(), sprintf('Only %.1f%% disk space free.', $percentFree), $context);
        }

        if ($percentFree < $degradedBelow) {
            return CheckResult::degraded($this->name(), sprintf('Only %.1f%% disk space free.', $percentFree), $context);
        }

        return CheckResult::up($this->name(), sprintf('%.1f%% disk space free.', $percentFree), $context);
    }
}
