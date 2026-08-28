<?php

declare(strict_types=1);

use GavTaylor\HealthRoute\Support\IpMatcher;

it('matches an exact IPv4 address', function () {
    expect(IpMatcher::matches('192.168.1.5', ['192.168.1.5']))->toBeTrue();
    expect(IpMatcher::matches('192.168.1.6', ['192.168.1.5']))->toBeFalse();
});

it('matches an IPv4 CIDR range', function () {
    expect(IpMatcher::matches('10.0.5.1', ['10.0.0.0/8']))->toBeTrue();
    expect(IpMatcher::matches('11.0.5.1', ['10.0.0.0/8']))->toBeFalse();
});

it('matches an exact IPv6 address', function () {
    expect(IpMatcher::matches('::1', ['::1']))->toBeTrue();
    expect(IpMatcher::matches('::2', ['::1']))->toBeFalse();
});

it('matches an IPv6 CIDR range', function () {
    expect(IpMatcher::matches('2001:db8::1', ['2001:db8::/32']))->toBeTrue();
    expect(IpMatcher::matches('2001:db9::1', ['2001:db8::/32']))->toBeFalse();
});

it('matches against multiple allowlist entries', function () {
    $allowlist = ['10.0.0.0/8', '192.168.1.5', '::1'];

    expect(IpMatcher::matches('192.168.1.5', $allowlist))->toBeTrue();
    expect(IpMatcher::matches('172.16.0.1', $allowlist))->toBeFalse();
});

it('never matches against an empty allowlist', function () {
    expect(IpMatcher::matches('127.0.0.1', []))->toBeFalse();
});

it('treats an invalid or malformed entry as a non-match rather than an error', function () {
    expect(IpMatcher::matches('127.0.0.1', ['not-an-ip', '']))->toBeFalse();
});

it('does not match a CIDR prefix that partially overlaps a byte boundary incorrectly', function () {
    expect(IpMatcher::matches('10.0.0.1', ['10.0.0.0/25']))->toBeTrue();
    expect(IpMatcher::matches('10.0.0.200', ['10.0.0.0/25']))->toBeFalse();
});
