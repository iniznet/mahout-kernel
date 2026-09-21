<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Kernel;

/**
 * Every hook the kernel emits or observes. Names are declared once, here.
 */
final class Hooks
{
    /**
     * Fires before the kernel registers providers and modules.
     *
     * @since 1.0
     *
     * @action
     *
     * @param Kernel $kernel the kernel about to boot
     */
    public const string BEFORE_BOOT = 'mahout/kernel/before_boot';

    /**
     * Fires after every provider and module registered and booted.
     *
     * @since 1.0
     *
     * @action
     *
     * @param Kernel $kernel the kernel that booted
     */
    public const string AFTER_BOOT = 'mahout/kernel/after_boot';

    /**
     * Filters the provider classes the kernel instantiates and registers.
     *
     * @since 1.0
     *
     * @filter
     *
     * @param list<class-string> $providers the declared provider classes
     */
    public const string PROVIDERS = 'mahout/kernel/providers';

    /**
     * Filters the module classes the kernel instantiates and registers.
     *
     * @since 1.0
     *
     * @filter
     *
     * @param list<class-string> $modules the declared module classes
     */
    public const string MODULES = 'mahout/kernel/modules';

    /**
     * Fires when a provider or a module throws during boot.
     *
     * @since 1.0
     *
     * @action
     *
     * @param \Throwable $failure the boot failure
     * @param Kernel     $kernel  the kernel that failed
     */
    public const string BOOT_FAILED = 'mahout/kernel/boot_failed';

    /**
     * Core's hook that fires for every action and filter. The development-only
     * hook validator observes it; it is not a mahout hook.
     *
     * @since 1.0
     *
     * @action
     *
     * @param string $hookName the hook that fired
     */
    public const string ALL = 'all';
}
