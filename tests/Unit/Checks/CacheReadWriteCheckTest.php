<?php

declare(strict_types=1);

use GavTaylor\HealthRoute\Checks\CacheReadWriteCheck;
use GavTaylor\HealthRoute\Checks\CheckStatus;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Support\Facades\Cache;

it('is up when it can write to and read from the cache', function () {
    $result = app(CacheReadWriteCheck::class)->run();

    expect($result->status)->toBe(CheckStatus::Up);
});

it('does not leave the probe key behind after a successful run', function () {
    app(CacheReadWriteCheck::class)->run();

    $store = Cache::getStore();
    $reflection = new ReflectionProperty($store, 'storage');
    $reflection->setAccessible(true);

    $remaining = collect(array_keys($reflection->getValue($store)))
        ->filter(fn (string $key) => str_starts_with($key, 'health-route.cache-check.'));

    expect($remaining)->toBeEmpty();
});

it('is down without leaking exception detail when the cache store fails', function () {
    Cache::extend('failing-store', fn () => Cache::repository(new class implements Store
    {
        public function get($key) {}

        public function many(array $keys) {}

        public function put($key, $value, $seconds)
        {
            throw new RuntimeException('cache backend unreachable at 10.0.0.5:6379');
        }

        public function putMany(array $values, $seconds) {}

        public function increment($key, $value = 1) {}

        public function decrement($key, $value = 1) {}

        public function forever($key, $value) {}

        public function forget($key)
        {
            return true;
        }

        public function touch($key, $seconds)
        {
            return true;
        }

        public function flush() {}

        public function getPrefix()
        {
            return '';
        }
    }));

    config(['cache.stores.failing-store' => ['driver' => 'failing-store']]);
    config(['health-route.checks_config.cache.store' => 'failing-store']);

    $result = app(CacheReadWriteCheck::class)->run();

    expect($result->status)->toBe(CheckStatus::Down);
    expect($result->message)->not->toContain('10.0.0.5');
});
