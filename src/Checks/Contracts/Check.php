<?php

declare(strict_types=1);

namespace GavTaylor\HealthRoute\Checks\Contracts;

use GavTaylor\HealthRoute\Checks\CheckResult;

interface Check
{
    /**
     * A short, human-readable name identifying this check in the response.
     */
    public function name(): string;

    /**
     * Run the check and report its result.
     *
     * Implementations should catch their own expected failure modes (e.g.
     * a connection exception) and translate them into a Down result with a
     * sanitised message - never leak an exception message, file path, or
     * stack trace into the response body.
     */
    public function run(): CheckResult;
}
