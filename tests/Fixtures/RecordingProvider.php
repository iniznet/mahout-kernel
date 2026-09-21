<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Kernel\Tests\Fixtures;

use Iniznet\Mahout\Kernel\Container;
use Iniznet\Mahout\Kernel\Contracts\ServiceProvider;

/**
 * @internal
 */
final class RecordingProvider implements ServiceProvider
{
    public function register(Container $container): void
    {
        BootLog::record('provider:register');
    }

    public function boot(Container $container): void
    {
        BootLog::record('provider:boot');
    }
}
