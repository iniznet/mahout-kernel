<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Kernel\Tests\Fixtures;

use Iniznet\Mahout\Kernel\Container;
use Iniznet\Mahout\Kernel\Contracts\Module;

/**
 * @internal
 */
final class RecordingModule implements Module
{
    public function register(Container $container): void
    {
        BootLog::record('module:register');
    }

    public function boot(Container $container): void
    {
        BootLog::record('module:boot');
    }
}
