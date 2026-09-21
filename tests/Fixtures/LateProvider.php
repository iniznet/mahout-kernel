<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Kernel\Tests\Fixtures;

use Iniznet\Mahout\Kernel\Container;
use Iniznet\Mahout\Kernel\Contracts\ServiceProvider;

/**
 * A provider that is only ever added through the mahout/kernel/providers filter.
 *
 * @internal
 */
final class LateProvider implements ServiceProvider
{
    public function register(Container $container): void
    {
        BootLog::record('late:register');
    }

    public function boot(Container $container): void
    {
        BootLog::record('late:boot');
    }
}
