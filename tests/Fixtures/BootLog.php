<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Kernel\Tests\Fixtures;

/**
 * The shared event log the boot fixtures append to.
 *
 * @internal
 */
final class BootLog
{
    /** @var list<string> */
    public static array $events = [];

    public static ?\Throwable $failure = null;

    public static function record(string $event): void
    {
        self::$events[] = $event;
    }

    public static function reset(): void
    {
        self::$events = [];
        self::$failure = null;
    }
}
