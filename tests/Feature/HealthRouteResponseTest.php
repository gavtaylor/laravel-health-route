<?php

declare(strict_types=1);

use GavTaylor\HealthRoute\Checks\CheckResult;
use GavTaylor\HealthRoute\Checks\Contracts\Check;
use Illuminate\Foundation\Events\DiagnosingHealth;
use Illuminate\Support\Facades\Event;

it('returns a status-only JSON payload matching Laravel core when no checks are configured', function () {
    $this->getJson('/up')
        ->assertOk()
        ->assertExactJson(['status' => 'up']);
});

it('renders the default HTML view with core-matching wording when healthy', function () {
    $this->get('/up')
        ->assertOk()
        ->assertSee('Application up');
});

it('dispatches DiagnosingHealth so an app-defined listener is honoured', function () {
    $dispatched = false;

    Event::listen(DiagnosingHealth::class, function () use (&$dispatched) {
        $dispatched = true;
    });

    $this->getJson('/up')->assertOk();

    expect($dispatched)->toBeTrue();
});

it('fails the check with a fixed 500 when a DiagnosingHealth listener throws, matching core', function () {
    Event::listen(DiagnosingHealth::class, function () {
        throw new RuntimeException('database connection refused at /var/www/secret/path');
    });

    $response = $this->getJson('/up');

    $response->assertStatus(500)->assertExactJson(['status' => 'down']);
    expect($response->getContent())->not->toContain('secret/path');
});

it('rethrows a DiagnosingHealth listener exception in debug mode instead of catching it', function () {
    config(['app.debug' => true]);

    Event::listen(DiagnosingHealth::class, function () {
        throw new RuntimeException('boom');
    });

    $this->withoutExceptionHandling();

    expect(fn () => $this->get('/up'))->toThrow(RuntimeException::class, 'boom');
});

it('never fails the HTTP response for a degraded check', function () {
    config(['health-route.checks' => [DegradedCheckStub::class]]);

    $this->getJson('/up')
        ->assertOk()
        ->assertJsonPath('status', 'up')
        ->assertJsonPath('checks.0.status', 'degraded');
});

it('uses the configured problem status code when a check is down', function () {
    config([
        'health-route.checks' => [DownCheckStub::class],
        'health-route.problem_status_code' => 503,
    ]);

    $this->getJson('/up')
        ->assertStatus(503)
        ->assertJsonPath('status', 'down')
        ->assertJsonPath('checks.0.status', 'down');
});

it('exposes each check name, status, and message in the JSON payload', function () {
    config(['health-route.checks' => [UpCheckStub::class]]);

    $this->getJson('/up')
        ->assertOk()
        ->assertJson([
            'status' => 'up',
            'checks' => [
                ['name' => 'stub-up', 'status' => 'up', 'message' => 'all good'],
            ],
        ]);
});

it('omits the checks key entirely when no checks are configured, matching core byte-for-byte', function () {
    $response = $this->getJson('/up');

    expect($response->json())->toBe(['status' => 'up']);
});

it('registers a named route for URL generation', function () {
    expect(route('health-route', absolute: false))->toBe('/up');
});

it('never leaks an exception message from a check that throws', function () {
    config(['health-route.checks' => [ThrowingCheckStub::class]]);

    $response = $this->getJson('/up');

    $response->assertStatus(503)->assertJsonPath('checks.0.status', 'down');
    expect($response->getContent())
        ->not->toContain('/etc/passwd')
        ->not->toContain('ThrowingCheckStub');
});

it('lists checks in the default HTML view', function () {
    config(['health-route.checks' => [UpCheckStub::class]]);

    $this->get('/up')
        ->assertOk()
        ->assertSee('stub-up')
        ->assertSee('all good');
});

it('shows a dash for a check with no message in the HTML view, but keeps the JSON message null', function () {
    config(['health-route.checks' => [NoMessageCheckStub::class]]);

    $response = $this->get('/up');

    $response->assertOk()->assertSee('stub-no-message');
    expect($response->getContent())->toContain('>-</td>');

    $this->getJson('/up')
        ->assertOk()
        ->assertJsonPath('checks.0.message', null);
});

it('sorts checks alphabetically by name in the HTML view, but keeps configured order in JSON', function () {
    config(['health-route.checks' => [ZebraCheckStub::class, AlphaCheckStub::class]]);

    $this->get('/up')->assertOk()->assertSeeInOrder(['alpha-check', 'zebra-check']);

    $this->getJson('/up')
        ->assertOk()
        ->assertJsonPath('checks.0.name', 'zebra-check')
        ->assertJsonPath('checks.1.name', 'alpha-check');
});

class NoMessageCheckStub implements Check
{
    public function name(): string
    {
        return 'stub-no-message';
    }

    public function run(): CheckResult
    {
        return CheckResult::up($this->name());
    }
}

class UpCheckStub implements Check
{
    public function name(): string
    {
        return 'stub-up';
    }

    public function run(): CheckResult
    {
        return CheckResult::up($this->name(), 'all good');
    }
}

class DegradedCheckStub implements Check
{
    public function name(): string
    {
        return 'stub-degraded';
    }

    public function run(): CheckResult
    {
        return CheckResult::degraded($this->name(), 'a bit slow');
    }
}

class DownCheckStub implements Check
{
    public function name(): string
    {
        return 'stub-down';
    }

    public function run(): CheckResult
    {
        return CheckResult::down($this->name(), 'unreachable');
    }
}

class ThrowingCheckStub implements Check
{
    public function name(): string
    {
        return 'stub-throwing';
    }

    public function run(): CheckResult
    {
        throw new RuntimeException('could not open /etc/passwd');
    }
}

class ZebraCheckStub implements Check
{
    public function name(): string
    {
        return 'zebra-check';
    }

    public function run(): CheckResult
    {
        return CheckResult::up($this->name());
    }
}

class AlphaCheckStub implements Check
{
    public function name(): string
    {
        return 'alpha-check';
    }

    public function run(): CheckResult
    {
        return CheckResult::up($this->name());
    }
}
