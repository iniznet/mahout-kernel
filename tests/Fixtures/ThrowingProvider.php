<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Kernel\Tests\Fixtures;

use Iniznet\Mahout\Kernel\Container;
use Iniznet\Mahout\Kernel\Contracts\ServiceProvider;

/**
 * @internal
 */
final class ThrowingProvider implements ServiceProvider
{
    public function register(Container $container): void
    {
        BootLog::record('throwing:register');

        throw BootLog::$failure ?? new \RuntimeException('the provider failed');
    }

    public function boot(Container $container): void
    {
        BootLog::record('throwing:boot');
    }
}
