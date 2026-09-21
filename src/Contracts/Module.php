<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Kernel\Contracts;

use Iniznet\Mahout\Kernel\Container;

/**
 * A domain slice: a content type, its repository and its hooks. A module is
 * registered after every provider and boots after every provider.
 */
interface Module
{
    /** Declare services and collaborators. Runs after provider registration. */
    public function register(Container $container): void;

    /** Attach domain hooks. Runs after every provider booted. */
    public function boot(Container $container): void;
}
