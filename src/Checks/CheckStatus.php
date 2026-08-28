<?php

declare(strict_types=1);

namespace GavTaylor\HealthRoute\Checks;

enum CheckStatus: string
{
    case Up = 'up';
    case Degraded = 'degraded';
    case Down = 'down';
}
