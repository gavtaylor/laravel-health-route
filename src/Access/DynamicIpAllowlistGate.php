<?php

declare(strict_types=1);

namespace GavTaylor\HealthRoute\Access;

use GavTaylor\HealthRoute\Access\Contracts\AccessGate;
use GavTaylor\HealthRoute\Access\Contracts\DnsResolver;
use GavTaylor\HealthRoute\Support\IpMatcher;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Http\Request;

/**
 * Allowlist gate for callers on a dynamic IP behind a DDNS hostname.
 *
 * Resolves each configured hostname via DNS and caches the result for the
 * record's own TTL, so a lookup only happens once per TTL window rather
 * than on every request. A failed/unresolvable lookup is cached too, for a
 * short window, so a DNS outage doesn't turn every request into a slow
 * one.
 */
final class DynamicIpAllowlistGate implements AccessGate
{
    public function __construct(
        private readonly CacheRepository $cache,
        private readonly DnsResolver $resolver,
    ) {
        //
    }

    public function authorizes(Request $request): bool
    {
        /** @var list<string> $hostnames */
        $hostnames = array_values(array_filter(config('health-route.access.allowed_hostnames', [])));

        if ($hostnames === []) {
            return false;
        }

        $ip = $request->ip();

        if ($ip === null) {
            return false;
        }

        foreach ($hostnames as $hostname) {
            if (IpMatcher::matches($ip, $this->resolve($hostname))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private function resolve(string $hostname): array
    {
        $cacheKey = 'health-route.dns.'.$hostname;

        /** @var list<string>|null $cached */
        $cached = $this->cache->get($cacheKey);

        if ($cached !== null) {
            return $cached;
        }

        [$ips, $ttl] = $this->resolver->resolve($hostname);

        $negativeTtl = max(1, (int) config('health-route.access.dynamic_ip.negative_cache_seconds', 30));

        $this->cache->put($cacheKey, $ips, $ips === [] ? $negativeTtl : max(1, $ttl));

        return $ips;
    }
}
