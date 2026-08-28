<?php

declare(strict_types=1);

use GavTaylor\HealthRoute\Checks\CheckStatus;
use GavTaylor\HealthRoute\Checks\PendingMigrationsCheck;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Facades\Artisan;

beforeEach(function () {
    config([
        'database.default' => 'sqlite',
        'database.connections.sqlite' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ],
    ]);

    $this->migrationsPath = sys_get_temp_dir().'/health-route-migrations-'.uniqid();
    mkdir($this->migrationsPath);

    app(Migrator::class)->path($this->migrationsPath);
});

afterEach(function () {
    array_map('unlink', glob($this->migrationsPath.'/*.php'));
    rmdir($this->migrationsPath);
});

function writeMigrationStub(string $path, string $name, string $table): void
{
    file_put_contents($path.'/'.$name.'.php', <<<PHP
        <?php
        use Illuminate\Database\Migrations\Migration;
        use Illuminate\Database\Schema\Blueprint;
        use Illuminate\Support\Facades\Schema;

        return new class extends Migration {
            public function up(): void
            {
                Schema::create('{$table}', function (Blueprint \$table) {
                    \$table->id();
                });
            }
        };
        PHP);
}

it('is degraded when the migrations table does not exist yet', function () {
    writeMigrationStub($this->migrationsPath, '2020_01_01_000000_create_first_table', 'first');

    $result = app(PendingMigrationsCheck::class)->run();

    expect($result->status)->toBe(CheckStatus::Degraded);
});

it('is up when every migration has been run', function () {
    writeMigrationStub($this->migrationsPath, '2020_01_01_000000_create_first_table', 'first');

    Artisan::call('migrate', ['--path' => $this->migrationsPath, '--realpath' => true, '--force' => true]);

    $result = app(PendingMigrationsCheck::class)->run();

    expect($result->status)->toBe(CheckStatus::Up);
});

it('is degraded and lists pending migrations when some have not run yet', function () {
    writeMigrationStub($this->migrationsPath, '2020_01_01_000000_create_first_table', 'first');
    Artisan::call('migrate', ['--path' => $this->migrationsPath, '--realpath' => true, '--force' => true]);

    writeMigrationStub($this->migrationsPath, '2020_01_02_000000_create_second_table', 'second');

    $result = app(PendingMigrationsCheck::class)->run();

    expect($result->status)->toBe(CheckStatus::Degraded);
    expect($result->context['pending'])->toContain('2020_01_02_000000_create_second_table');
});
