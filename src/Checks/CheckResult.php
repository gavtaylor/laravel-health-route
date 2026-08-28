<?php

declare(strict_types=1);

namespace GavTaylor\HealthRoute\Checks;

final readonly class CheckResult
{
    /**
     * @param  array<string, mixed>|null  $context
     */
    public function __construct(
        public string $name,
        public CheckStatus $status,
        public ?string $message = null,
        public ?array $context = null,
    ) {
        //
    }

    /**
     * @param  array<string, mixed>|null  $context
     */
    public static function up(string $name, ?string $message = null, ?array $context = null): self
    {
        return new self($name, CheckStatus::Up, $message, $context);
    }

    /**
     * @param  array<string, mixed>|null  $context
     */
    public static function degraded(string $name, ?string $message = null, ?array $context = null): self
    {
        return new self($name, CheckStatus::Degraded, $message, $context);
    }

    /**
     * @param  array<string, mixed>|null  $context
     */
    public static function down(string $name, ?string $message = null, ?array $context = null): self
    {
        return new self($name, CheckStatus::Down, $message, $context);
    }

    /**
     * @return array{name: string, status: string, message: string|null}
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'status' => $this->status->value,
            'message' => $this->message,
        ];
    }
}
