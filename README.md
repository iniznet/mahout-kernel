# mahout-kernel

## What it is

The kernel every mahout package depends on: one boot sequence, an explicit
service container, typed environment configuration, hook registration and the
per-request `Diagnostics` record. It owns no theme, no content type and no
storage; it owns the order in which the rest of the family is wired.

## Installation

There is no Packagist lane. Consume the repository over VCS and pin the major:

```json
{
    "repositories": [
        { "type": "vcs", "url": "https://github.com/iniznet/mahout-kernel.git" }
    ],
    "require": {
        "iniznet/mahout-kernel": "^1.0"
    }
}
```

```bash
composer require iniznet/mahout-kernel:^1.0
```

A development checkout points at a sibling directory through an uncommitted
`composer.dev.json` (a `path` repository plus `@dev`) and runs
`COMPOSER=composer.dev.json composer install`.

## The public `Contracts/` surface

`src/Contracts/` is the package's entire public API. Everything under
`src/Internal/` is `@internal` and may change in a patch release.

| Interface | Role | Implementations |
|---|---|---|
| `ServiceProvider` | infrastructure registration and boot | consumer providers; the package's test fixtures |
| `Module` | domain registration and boot | consumer modules; the package's test fixtures |
| `QuerySource` | the query count and, in development, the query text | `Internal\WpdbQuerySource` |
| `BootFailureResponder` | the `never`-returning boot-failure exit | `Internal\WordPressBootFailureResponder` |

## Minimal usage

The composition root declares every service, provider and module inline and
calls `boot()` once. Nothing is registered at file scope.

```php
<?php

declare(strict_types=1);

use Iniznet\Mahout\Kernel\Diagnostics;
use Iniznet\Mahout\Kernel\Kernel;

$kernel = Kernel::inWordPress();

$kernel->service(new Diagnostics($kernel->environment(), /* a QuerySource */));

$kernel->provider(ThemeProvider::class);
$kernel->module(SeriesModule::class);

$kernel->boot();

$services = $kernel->services();
```

A provider receives the `Container` in `register()` and `boot()`:

```php
final class ThemeProvider implements ServiceProvider
{
    public function register(Container $container): void
    {
        $container->set(new ThemeSupport());

        // A collaborator a consumer depends on by contract is declared under
        // that contract, and resolved by it.
        $container->set(service: new SeriesRepository(), id: SeriesLookup::class);
    }

    public function boot(Container $container): void
    {
        add_action('after_setup_theme', [$container->get(ThemeSupport::class), 'apply']);
    }
}
```

`set()` registers a service under the key it is resolved by. The key defaults to the
service's own class name; pass a `Contracts` interface to declare the service by contract.
`get()` is a typed lookup, so a caller resolving an interface receives that interface:

```php
$container->set(service: $connection, id: SqlConnection::class);

$container->get(SqlConnection::class);   // SqlConnection
```

The analyzer checks at the call site that the service satisfies the key, and
`ServiceKeyMismatch` is thrown when a dynamically built call does not. The key
is a class-string, never an arbitrary identifier: there is no reflection and no
autowiring, and a service is resolvable only because a line declared it.

## Documented public concrete classes

Every documented public class is part of the stable surface within a major.

| Class | Role |
|---|---|
| `Kernel` | the boot sequence, the declared provider and module lists, the container |
| `Container` | the explicit declared service registry (`set`, `has`, `get`, `ids`) |
| `Diagnostics` | query count, spans, typed records and the one `error_log()` call site |
| `Environment` | `type`, `debug`, `developmentMode`, the log threshold and error exposure |
| `Record` | one diagnostics entry: level, message, typed context, support reference |
| `Level` | the ordered `Debug`.. `Critical` enum |
| `HookKind` | the `Action` / `Filter` enum |
| `Hooks` | every hook constant the kernel emits or observes |

The kernel emits `mahout/kernel/before_boot`, `mahout/kernel/after_boot` and
`mahout/kernel/boot_failed`, and filters `mahout/kernel/providers` and
`mahout/kernel/modules`. The generated references are `docs/reference/actions.md` and `docs/reference/filters.md`.

## Compatibility

| Item | Value |
|---|---|
| PHP | 8.4 or later |
| WordPress | 7.1 or later |
| `Contracts/` | stable within a major version; a change is a contract change and is published as a major |
| `Internal/` | unguaranteed; may change in a patch release |
| Licence | GPL-2.0-or-later |

## Architecture

The kernel is a modular monolith's composition boundary. Providers register
before modules, modules before provider boot, provider boot before module boot,
and `after_boot` fires only when all four succeed. A failure records at
`critical`, fires `mahout/kernel/boot_failed` with the `Throwable` and the
`Kernel`, and then stops the request: development rethrows the trace, production
renders a translated 500. No half-registered graph is ever served.

The canonical planning corpus is private and is not published with this
repository; the decisions this package made are recorded under
`docs/decisions/` and the discipline contract is `AGENTS.md`.

## Licence

GPL-2.0-or-later. The full text is in [LICENSE](./LICENSE).
