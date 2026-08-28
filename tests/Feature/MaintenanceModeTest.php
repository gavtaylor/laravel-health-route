<?php

declare(strict_types=1);

it('remains reachable during maintenance mode', function () {
    $this->artisan('down')->assertExitCode(0);

    try {
        $this->getJson('/up')->assertOk()->assertExactJson(['status' => 'up']);
    } finally {
        $this->artisan('up');
    }
});
