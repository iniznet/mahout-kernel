<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Kernel\Tests\Unit;

use Iniznet\Mahout\Kernel\Level;
use Iniznet\Mahout\Kernel\Tests\TestCase;

/**
 * @internal
 */
final class LevelTest extends TestCase
{
    public function testAtLeastOrdersTheSeverities(): void
    {
        self::assertTrue(Level::Critical->atLeast(Level::Error));
        self::assertTrue(Level::Error->atLeast(Level::Error));
        self::assertFalse(Level::Warning->atLeast(Level::Error));
        self::assertTrue(Level::Debug->atLeast(Level::Debug));
    }
}
