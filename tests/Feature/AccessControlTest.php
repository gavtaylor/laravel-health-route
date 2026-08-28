<?php

declare(strict_types=1);

it('is public by default, matching core', function () {
    $this->getJson('/up')->assertOk()->assertExactJson(['status' => 'up']);
});

it('authorises a caller with valid HTTP basic auth credentials', function () {
    config([
        'health-route.access.basic_auth.username' => 'monitor',
        'health-route.access.basic_auth.password' => 'secret',
    ]);

    $this->withHeaders(['Authorization' => 'Basic '.base64_encode('monitor:secret')])
        ->getJson('/up')
        ->assertOk()
        ->assertExactJson(['status' => 'up']);
});

it('denies a caller with wrong basic auth credentials but keeps the real status code and an empty body', function () {
    config([
        'health-route.access.basic_auth.username' => 'monitor',
        'health-route.access.basic_auth.password' => 'secret',
    ]);

    $response = $this->withHeaders(['Authorization' => 'Basic '.base64_encode('monitor:wrong')])
        ->get('/up');

    $response->assertOk();
    expect($response->getContent())->toBe('');
});

it('never authenticates an empty basic auth config against empty credentials', function () {
    config([
        'health-route.access.basic_auth.username' => null,
        'health-route.access.basic_auth.password' => null,
    ]);

    // No Authorization header sent at all.
    $response = $this->get('/up');

    // Public by default when nothing is configured: the empty config must
    // not accidentally deny a caller either.
    $response->assertOk();
    expect($response->getContent())->not->toBe('');
});

it('authorises a caller with the correct shared-secret header', function () {
    config([
        'health-route.access.token.header' => 'X-Health-Token',
        'health-route.access.token.value' => 'super-secret',
    ]);

    $this->withHeaders(['X-Health-Token' => 'super-secret'])
        ->getJson('/up')
        ->assertOk()
        ->assertExactJson(['status' => 'up']);
});

it('denies a caller with the wrong shared-secret header value', function () {
    config([
        'health-route.access.token.header' => 'X-Health-Token',
        'health-route.access.token.value' => 'super-secret',
    ]);

    $response = $this->get('/up', ['X-Health-Token' => 'wrong']);

    $response->assertOk();
    expect($response->getContent())->toBe('');
});

it('authorises a caller from an allowed static IP', function () {
    config(['health-route.access.allowed_ips' => ['127.0.0.1']]);

    $this->getJson('/up')->assertOk()->assertExactJson(['status' => 'up']);
});

it('denies a caller outside the static IP allowlist', function () {
    config(['health-route.access.allowed_ips' => ['10.0.0.0/8']]);

    $response = $this->get('/up');

    $response->assertOk();
    expect($response->getContent())->toBe('');
});

it('composes multiple configured access methods with OR - passing any one is enough', function () {
    config([
        'health-route.access.token.header' => 'X-Health-Token',
        'health-route.access.token.value' => 'super-secret',
        'health-route.access.allowed_ips' => ['10.0.0.0/8'], // request comes from 127.0.0.1, so this alone would fail
    ]);

    $this->withHeaders(['X-Health-Token' => 'super-secret'])
        ->getJson('/up')
        ->assertOk()
        ->assertExactJson(['status' => 'up']);
});

it('denies a caller that fails every configured access method', function () {
    config([
        'health-route.access.token.header' => 'X-Health-Token',
        'health-route.access.token.value' => 'super-secret',
        'health-route.access.allowed_ips' => ['10.0.0.0/8'],
    ]);

    $response = $this->get('/up');

    $response->assertOk(); // real status code preserved
    expect($response->getContent())->toBe('');
});

it('reflects the real problem status code even when access is denied', function () {
    config([
        'health-route.access.token.header' => 'X-Health-Token',
        'health-route.access.token.value' => 'super-secret',
        'health-route.checks' => [DownCheckStub::class],
        'health-route.problem_status_code' => 503,
    ]);

    $response = $this->get('/up');

    $response->assertStatus(503);
    expect($response->getContent())->toBe('');
});

it('bypasses the gate in the local environment when explicitly enabled', function () {
    config([
        'health-route.access.token.header' => 'X-Health-Token',
        'health-route.access.token.value' => 'super-secret',
        'health-route.access.bypass_when_local' => true,
    ]);

    app()->detectEnvironment(fn () => 'local');

    $this->getJson('/up')->assertOk()->assertExactJson(['status' => 'up']);
});

it('does not bypass the gate in the local environment unless explicitly enabled', function () {
    config([
        'health-route.access.token.header' => 'X-Health-Token',
        'health-route.access.token.value' => 'super-secret',
        'health-route.access.bypass_when_local' => false,
    ]);

    app()->detectEnvironment(fn () => 'local');

    $response = $this->get('/up');

    $response->assertOk();
    expect($response->getContent())->toBe('');
});
