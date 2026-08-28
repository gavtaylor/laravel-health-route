<?php

declare(strict_types=1);

use GavTaylor\HealthRoute\Support\WritableDirectory;

it('is writable when the directory exists and accepts a probe file', function () {
    expect(WritableDirectory::check(sys_get_temp_dir()))->toBeTrue();
});

it('is not writable when the directory does not exist', function () {
    expect(WritableDirectory::check('/this/path/does/not/exist'))->toBeFalse();
});

it('does not leave the probe file behind', function () {
    $dir = sys_get_temp_dir().'/health-route-writable-'.uniqid();
    mkdir($dir);

    WritableDirectory::check($dir);

    expect(glob($dir.'/.health-route-*'))->toBeEmpty();

    rmdir($dir);
});
