<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Kernel\Tests\Unit;

use Iniznet\Mahout\Kernel\Environment;
use Iniznet\Mahout\Kernel\Level;
use Iniznet\Mahout\Kernel\Tests\TestCase;

/**
 * @internal
 */
final class EnvironmentTest extends TestCase
{
    public function testDevelopmentAndLocalExposeErrors(): void
    {
        self::assertTrue((new Environment(type: 'development', debug: false, developmentMode: false))->exposesErrors());
        self::assertTrue((new Environment(type: 'local', debug: false, developmentMode: false))->exposesErrors());
        self::assertTrue((new Environment(type: 'production', debug: true, developmentMode: false))->exposesErrors());
        self::assertFalse((new Environment(type: 'production', debug: false, developmentMode: false))->exposesErrors());
    }

    public function testTheLogThresholdFollowsTheEnvironment(): void
    {
        self::assertSame(Level::Debug, (new Environment(type: 'development', debug: false, developmentMode: false))->logThreshold());
        self::assertSame(Level::Warning, (new Environment(type: 'staging', debug: false, developmentMode: false))->logThreshold());
        self::assertSame(Level::Error, (new Environment(type: 'production', debug: false, developmentMode: false))->logThreshold());
    }

    public function testProductionIsNamed(): void
    {
        self::assertTrue((new Environment(type: 'production', debug: false, developmentMode: false))->isProduction());
        self::assertFalse((new Environment(type: 'staging', debug: false, developmentMode: false))->isProduction());
    }
}
