<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Kernel\Contracts;

use Iniznet\Mahout\Kernel\Container;

/**
 * Infrastructure wiring: assets, REST, admin, CLI. A provider declares its
 * services in register() and attaches hooks in boot().
 */
interface ServiceProvider
{
    /** Declare services and collaborators. Runs after before_boot fires. */
    public function register(Container $container): void;

    /** Attach hooks and side effects. Runs after every module registered. */
    public function boot(Container $container): void;
}
