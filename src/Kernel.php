<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Kernel;

use Iniznet\Mahout\Kernel\Contracts\BootFailureResponder;
use Iniznet\Mahout\Kernel\Contracts\Module;
use Iniznet\Mahout\Kernel\Contracts\ServiceProvider;
use Iniznet\Mahout\Kernel\Exception\InvalidHookPayload;
use Iniznet\Mahout\Kernel\Exception\KernelAlreadyBooted;
use Iniznet\Mahout\Kernel\Exception\KernelBootFailed;
use Iniznet\Mahout\Kernel\Exception\SecondCompositionRoot;
use Iniznet\Mahout\Kernel\Internal\ProcessClaim;
use Iniznet\Mahout\Kernel\Internal\WordPressBootFailureResponder;
use Iniznet\Mahout\Kernel\Internal\WpdbQuerySource;

/**
 * The kernel: the explicit service registry and the single boot sequence.
 *
 * Providers and modules are named inline by the composition root and are
 * instantiated by class name after the mahout/kernel/providers and
 * mahout/kernel/modules filters ran. No reflection, no autowiring, no config
 * file between the reader and the registration.
 */
final class Kernel
{
    private readonly Container $container;

    /** @var list<class-string<ServiceProvider>> */
    private array $providerClasses = [];

    /** @var list<class-string<Module>> */
    private array $moduleClasses = [];

    /** @var list<ServiceProvider> */
    private array $providers = [];

    /** @var list<Module> */
    private array $modules = [];

    private bool $booted = false;

    public function __construct(
        private readonly Environment $environment,
        private readonly Diagnostics $diagnostics,
        private readonly BootFailureResponder $bootFailures,
    ) {
        $this->container = new Container();
        $this->container->set($environment);
        $this->container->set($diagnostics);
    }

    /**
     * The composition-root named constructor: the three WordPress facts and the
     * wpdb query source, read once, inside one boundary.
     *
     * The root names itself, and the naming is not decoration. Two hosts that each
     * install the packages — a theme and a plugin, say — do not fail loudly when
     * they meet: one autoloader wins and serves its copies to both, while the
     * schema-version option, the field tables and the hook namespace are shared with
     * no owner recorded anywhere. A process may have one root of record, so the
     * second distinct claim is refused here, before a single hook is attached,
     * rather than reconciled later in the request.
     *
     * @param class-string $root the composition root booting this process, normally `self::class`
     *
     * @throws SecondCompositionRoot when a different root has already claimed this process
     */
    public static function inWordPress(string $root): self
    {
        ProcessClaim::claim($root);

        $environment = Environment::fromWordPress();
        $wpdb = $GLOBALS['wpdb'] ?? null;

        if (!$wpdb instanceof \wpdb) {
            throw KernelBootFailed::withoutDatabase();
        }

        return new self(
            environment: $environment,
            diagnostics: new Diagnostics($environment, new WpdbQuerySource($wpdb)),
            bootFailures: new WordPressBootFailureResponder($environment),
        );
    }

    /**
     * Declare a provider by class name. The filter may append to the list.
     *
     * @param class-string<ServiceProvider> $provider
     */
    public function provider(string $provider): self
    {
        $this->providerClasses[] = $provider;

        return $this;
    }

    /**
     * Declare a module by class name. The filter may append to the list.
     *
     * @param class-string<Module> $module
     */
    public function module(string $module): self
    {
        $this->moduleClasses[] = $module;

        return $this;
    }

    /**
     * Register an already-built service under its own class name, or under the
     * Contracts interface a consumer depends on.
     *
     * @template T of object
     *
     * @param T                    $service
     * @param class-string<T>|null $id
     */
    public function service(object $service, ?string $id = null): self
    {
        $this->container->set($service, $id);

        return $this;
    }

    /**
     * Register and boot everything, exactly once.
     *
     * A failure anywhere in the four steps is recorded at critical, fired as
     * mahout/kernel/boot_failed with the Throwable and this Kernel, and then
     * handed to the responder, which never returns.
     */
    public function boot(): void
    {
        if ($this->booted) {
            throw KernelAlreadyBooted::secondCall();
        }

        do_action(Hooks::BEFORE_BOOT, $this);

        try {
            $this->registerProviders();
            $this->registerModules();
            $this->bootProviders();
            $this->bootModules();
        } catch (\Throwable $failure) {
            $this->diagnostics->log(
                level: Level::Critical,
                message: 'kernel boot failed',
                context: ['exception' => $failure],
            );

            do_action(Hooks::BOOT_FAILED, $failure, $this);

            $this->bootFailures->failed($failure);
        }

        $this->booted = true;

        do_action(Hooks::AFTER_BOOT, $this);
    }

    public function services(): Container
    {
        return $this->container;
    }

    public function diagnostics(): Diagnostics
    {
        return $this->diagnostics;
    }

    public function environment(): Environment
    {
        return $this->environment;
    }

    public function booted(): bool
    {
        return $this->booted;
    }

    private function registerProviders(): void
    {
        foreach ($this->classes(Hooks::PROVIDERS, $this->providerClasses) as $instance) {
            if (!$instance instanceof ServiceProvider) {
                throw InvalidHookPayload::notAProvider(Hooks::PROVIDERS);
            }

            $instance->register($this->container);
            $this->providers[] = $instance;
        }
    }

    private function registerModules(): void
    {
        foreach ($this->classes(Hooks::MODULES, $this->moduleClasses) as $instance) {
            if (!$instance instanceof Module) {
                throw InvalidHookPayload::notAModule(Hooks::MODULES);
            }

            $instance->register($this->container);
            $this->modules[] = $instance;
        }
    }

    private function bootProviders(): void
    {
        foreach ($this->providers as $provider) {
            $provider->boot($this->container);
        }
    }

    private function bootModules(): void
    {
        foreach ($this->modules as $module) {
            $module->boot($this->container);
        }
    }

    /**
     * Instantiate the filtered class list. A filter that returns anything other
     * than existing classes stops the boot.
     *
     * @param list<class-string> $declared
     *
     * @return list<object>
     */
    private function classes(string $hook, array $declared): array
    {
        $filtered = apply_filters($hook, $declared);

        if (!\is_array($filtered)) {
            throw InvalidHookPayload::notAList($hook);
        }

        $instances = [];
        foreach ($filtered as $class) {
            if (!\is_string($class) || !class_exists($class)) {
                throw InvalidHookPayload::notAClass($hook);
            }

            $instances[] = new $class();
        }

        return $instances;
    }
}
