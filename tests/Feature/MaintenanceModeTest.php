<?php

declare(strict_types=1);

use GavTaylor\HealthRoute\Checks\CheckResult;
use GavTaylor\HealthRoute\Checks\Contracts\Check;

beforeEach(function () {
    $this->artisan('down')->assertExitCode(0);
});

afterEach(function () {
    $this->artisan('up');
});

it('remains reachable during maintenance mode with 200, matching core', function () {
    $this->getJson('/up')
        ->assertOk()
        ->assertExactJson(['status' => 'up', 'maintenance' => true]);
});

it('returns the configured maintenance status code, still reporting the app healthy', function () {
    config(['health-route.maintenance_status_code' => 503]);

    $this->getJson('/up')
        ->assertStatus(503)
        ->assertExactJson(['status' => 'up', 'maintenance' => true]);
});

it('treats an empty maintenance status code as unset', function () {
    config(['health-route.maintenance_status_code' => '']);

    $this->getJson('/up')->assertOk();
});

it('still runs every check during maintenance mode, and a down check wins', function () {
    config([
        'health-route.checks' => [MaintenanceDownCheckStub::class],
        'health-route.problem_status_code' => 500,
        'health-route.maintenance_status_code' => 503,
    ]);

    $this->getJson('/up')
        ->assertStatus(500)
        ->assertJsonPath('status', 'down')
        ->assertJsonPath('maintenance', true)
        ->assertJsonPath('checks.0.status', 'down');
});

it('says so in the default HTML view', function () {
    $this->get('/up')
        ->assertOk()
        ->assertSee('Application up')
        ->assertSee('In maintenance mode');
});

it('leaves the maintenance key out once the application is back up', function () {
    $this->artisan('up');

    $this->getJson('/up')->assertOk()->assertExactJson(['status' => 'up']);
    $this->get('/up')->assertDontSee('In maintenance mode');
});

class MaintenanceDownCheckStub implements Check
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
