<?php

declare(strict_types=1);

namespace GavTaylor\HealthRoute\Checks;

use GavTaylor\HealthRoute\Checks\Contracts\Check;
use GavTaylor\HealthRoute\Support\WritableDirectory;

/**
 * Confirms Laravel's own storage subdirectories are actually writable -
 * catches permission/ownership problems that DiskSpaceCheck (free space)
 * can't see. Log directory writability is covered separately by
 * LogWritableCheck.
 */
final class StorageWritableCheck implements Check
{
    public function name(): string
    {
        return 'storage';
    }

    public function run(): CheckResult
    {
        /** @var list<string> $relativePaths */
        $relativePaths = config('health-route.checks_config.storage.paths', [
            'framework/cache',
            'framework/sessions',
            'framework/views',
        ]);

        $unwritable = array_values(array_filter(
            $relativePaths,
            fn (string $relative): bool => ! WritableDirectory::check(storage_path($relative)),
        ));

        if ($unwritable !== []) {
            return CheckResult::down(
                $this->name(),
                sprintf('Not writable: %s.', implode(', ', $unwritable)),
                ['unwritable' => $unwritable],
            );
        }

        return CheckResult::up($this->name());
    }
}
