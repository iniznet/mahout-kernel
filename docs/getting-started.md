# Getting started

## Install

```bash
composer require iniznet/mahout-kernel:^1.0
```

A development checkout points at sibling directories through an uncommitted
`composer.dev.json` (path repositories plus `@dev`) and runs
`COMPOSER=composer.dev.json composer install`.

## Boot your first kernel

The composition root declares every service, provider and module inline and
calls `boot()` once. Nothing is registered at file scope.

```php
use Iniznet\Mahout\Kernel\Kernel;

$kernel = Kernel::inWordPress();

$kernel->service(new Diagnostics($kernel->environment(), /* a QuerySource */));
$kernel->provider(ThemeProvider::class);
$kernel->module(SeriesModule::class);

$kernel->boot();

$services = $kernel->services();
```

## Declare a service under a contract

A service is resolved by the key it was declared under. The key defaults to
the service's class; passing a `Contracts` interface declares it by contract,
which is how a consumer depends on an interface:

```php
$container->set(service: new SeriesRepository(), id: SeriesLookup::class);

$container->get(SeriesLookup::class);   // typed: the interface
```

The analyzer checks at the call site that the service satisfies the key, and
`ServiceKeyMismatch` is thrown when a dynamically built call does not. There
is no reflection and no autowiring: a service is resolvable only because a
line declared it.

## A provider, in full

```php
final class ThemeProvider implements ServiceProvider
{
    public function register(Container $container): void
    {
        $container->set(new ThemeSupport());
    }

    public function boot(Container $container): void
    {
        \add_action('after_setup_theme', [$container->get(ThemeSupport::class), 'apply']);
    }
}
```

`register()` declares services; `boot()` attaches to core hooks. Providers
register before modules, modules before provider boot, provider boot before
module boot, and `mahout/kernel/after_boot` fires only when all four succeed.

## Failure modes

| Symptom | Cause |
|---|---|
| `ServiceKeyMismatch` | a service was resolved under a key it does not satisfy |
| `NotBooted` | `services()` read before `boot()` |
| The request stopped at boot | a boot failure recorded at `critical`, fired `mahout/kernel/boot_failed` and stopped the request — development rethrows the trace, production renders a translated 500. No half-registered graph is ever served |
