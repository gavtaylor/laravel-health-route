<?php

declare(strict_types=1);

namespace GavTaylor\HealthRoute\Support;

use Illuminate\Support\Str;

final class WritableDirectory
{
    /**
     * Whether the given directory exists and can actually be written to -
     * confirmed by writing and removing a small probe file, not just
     * inspecting permission bits (which can lie under ACLs, overlay
     * filesystems, or a read-only bind mount).
     */
    public static function check(string $path): bool
    {
        if (! is_dir($path)) {
            return false;
        }

        $probe = rtrim($path, '/').'/.health-route-'.Str::random(16);

        if (@file_put_contents($probe, '') === false) {
            return false;
        }

        @unlink($probe);

        return true;
    }
}
