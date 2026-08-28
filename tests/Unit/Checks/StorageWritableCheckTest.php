<?php

declare(strict_types=1);

use GavTaylor\HealthRoute\Checks\CheckStatus;
use GavTaylor\HealthRoute\Checks\StorageWritableCheck;

beforeEach(function () {
    $this->storagePath = sys_get_temp_dir().'/health-route-storage-'.uniqid();
    mkdir($this->storagePath.'/framework/cache', recursive: true);
    mkdir($this->storagePath.'/framework/sessions', recursive: true);
    mkdir($this->storagePath.'/framework/views', recursive: true);

    app()->useStoragePath($this->storagePath);
});

afterEach(function () {
    $paths = array_reverse(glob($this->storagePath.'/*/*') ?: []);
    array_map('rmdir', $paths);
    array_map('rmdir', glob($this->storagePath.'/framework') ?: []);
    @rmdir($this->storagePath);
});

it('is up when every configured path is writable', function () {
    expect(app(StorageWritableCheck::class)->run()->status)->toBe(CheckStatus::Up);
});

it('is down and names the unwritable path when one is not writable', function () {
    chmod($this->storagePath.'/framework/cache', 0500);

    $result = app(StorageWritableCheck::class)->run();

    expect($result->status)->toBe(CheckStatus::Down);
    expect($result->message)->toContain('framework/cache');
    expect($result->context['unwritable'])->toBe(['framework/cache']);

    chmod($this->storagePath.'/framework/cache', 0700);
})->skip(fn () => PHP_OS_FAMILY === 'Windows', 'chmod is not meaningful on Windows');
