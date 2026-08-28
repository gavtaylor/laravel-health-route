<?php

declare(strict_types=1);

namespace GavTaylor\HealthRoute\Access\Contracts;

interface DnsResolver
{
    /**
     * Resolve a hostname's A/AAAA records.
     *
     * @return array{0: list<string>, 1: int} The resolved IPs, and the
     *                                        minimum TTL (in seconds)
     *                                        across the returned records.
     */
    public function resolve(string $hostname): array;
}
