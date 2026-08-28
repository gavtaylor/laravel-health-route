<?php

declare(strict_types=1);

namespace GavTaylor\HealthRoute\Access;

use GavTaylor\HealthRoute\Access\Contracts\DnsResolver;

/**
 * PHP has no reliable built-in timeout for DNS lookups. This is an
 * accepted limitation, documented in the README: it can only ever affect
 * the one request unlucky enough to trigger a cache-miss lookup during a
 * DNS outage, not the endpoint as a whole.
 */
final class PhpDnsResolver implements DnsResolver
{
    public function resolve(string $hostname): array
    {
        $records = @dns_get_record($hostname, DNS_A + DNS_AAAA);

        if (! is_array($records) || $records === []) {
            return [[], 0];
        }

        $ips = [];
        $ttl = null;

        foreach ($records as $record) {
            $ip = $record['ip'] ?? $record['ipv6'] ?? null;

            if (! is_string($ip)) {
                continue;
            }

            $ips[] = $ip;

            $recordTtl = (int) ($record['ttl'] ?? 0);
            $ttl = $ttl === null ? $recordTtl : min($ttl, $recordTtl);
        }

        return [$ips, $ttl ?? 0];
    }
}
