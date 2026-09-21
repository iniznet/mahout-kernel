<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Kernel\Tests\Fixtures;

use Iniznet\Mahout\Kernel\Container;
use Iniznet\Mahout\Kernel\Contracts\QuerySource;
use Iniznet\Mahout\Kernel\Contracts\ServiceProvider;

/**
 * A provider that declares a collaborator under the Contracts interface a
 * consumer depends on, the way a real package's provider does.
 *
 * @internal
 */
final class ContractBoundProvider implements ServiceProvider
{
    public function register(Container $container): void
    {
        $container->set(service: new InMemoryQuerySource(), id: QuerySource::class);
    }

    public function boot(Container $container): void
    {
        BootLog::record('contract:'.$container->get(QuerySource::class)::class);
    }
}
