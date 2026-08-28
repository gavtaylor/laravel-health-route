<?php

declare(strict_types=1);

namespace GavTaylor\HealthRoute\Support;

final class IpMatcher
{
    /**
     * Whether the given IP matches any entry in the allowlist.
     *
     * Each entry may be a single IP (IPv4 or IPv6) or a CIDR range (e.g.
     * "10.0.0.0/8" or "2001:db8::/32").
     *
     * @param  list<string>  $allowlist
     */
    public static function matches(string $ip, array $allowlist): bool
    {
        foreach ($allowlist as $entry) {
            if (self::matchesEntry($ip, $entry)) {
                return true;
            }
        }

        return false;
    }

    private static function matchesEntry(string $ip, string $entry): bool
    {
        $entry = trim($entry);

        if ($entry === '') {
            return false;
        }

        if (! str_contains($entry, '/')) {
            return self::inetPtoN($entry) !== null && self::inetPtoN($entry) === self::inetPtoN($ip);
        }

        [$subnet, $prefixLength] = explode('/', $entry, 2);

        $ipBinary = self::inetPtoN($ip);
        $subnetBinary = self::inetPtoN($subnet);

        if ($ipBinary === null || $subnetBinary === null || strlen($ipBinary) !== strlen($subnetBinary)) {
            return false;
        }

        $prefixLength = (int) $prefixLength;
        $maxLength = strlen($ipBinary) * 8;

        if ($prefixLength < 0 || $prefixLength > $maxLength) {
            return false;
        }

        $bytes = intdiv($prefixLength, 8);
        $remainderBits = $prefixLength % 8;

        if ($bytes > 0 && substr($ipBinary, 0, $bytes) !== substr($subnetBinary, 0, $bytes)) {
            return false;
        }

        if ($remainderBits === 0) {
            return true;
        }

        $mask = chr((0xFF << (8 - $remainderBits)) & 0xFF);

        return (substr($ipBinary, $bytes, 1) & $mask) === (substr($subnetBinary, $bytes, 1) & $mask);
    }

    private static function inetPtoN(string $ip): ?string
    {
        $binary = @inet_pton($ip);

        return $binary === false ? null : $binary;
    }
}
