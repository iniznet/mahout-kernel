<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Kernel;

/**
 * A Diagnostics severity, ordered from most verbose to most severe.
 *
 * The enum backs the ordering with its value so a threshold comparison cannot
 * silently invert when a case is added.
 */
enum Level: int
{
    case Debug = 100;
    case Info = 200;
    case Warning = 300;
    case Error = 400;
    case Critical = 500;

    public function atLeast(self $threshold): bool
    {
        return $this->value >= $threshold->value;
    }
}
