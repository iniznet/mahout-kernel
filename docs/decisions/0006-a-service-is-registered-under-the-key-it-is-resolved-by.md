# ADR-0006: a service is registered under the key it is resolved by

Status: accepted. Amends ADR-0002, which is unchanged in every other respect.

## Context

`Container::set(object $service)` stored the service under `$service::class` and nothing else, so
nothing could be declared or resolved under a `Contracts` interface.

That defeats the package's public surface. `Contracts/` is the public API precisely so that a
consumer depends on the interface and not on the implementation; a container that can only key by the
concrete class forces every consumer either to name an implementation class it is not allowed to
depend on, or to work around the container. `iniznet/mahout-db` did the second: it read its own
database connection from the WordPress global inside its provider and resolved it by concrete class,
and ADR-0004 there recorded the container's limitation as part of the reason.

The properties the container must keep while gaining a contract key:

- **Explicit.** Every dependency is greppable from the composition root; the declaration line names
  the key.
- **Statically traceable.** `get()` returns the type its argument declares, with no cast and no
  downcast at the call site.
- **No reflection, no autowiring.** A service is resolvable only because a line declared it.
- **No service locator.** `Container::get()` called statically is still banned by the shared
  architecture rule, unchanged.
- **No hidden resolution.** There is no lookup by concrete class for a service declared by contract,
  and no key derived from a class's implemented interfaces.

## Decision

`Container::set()` takes the key it registers under, and the key defaults to the service's own class
name:

```php
/** @template T of object
 *  @param T                   $service
 *  @param class-string<T>|null $id */
public function set(object $service, ?string $id = null): void
```

- The key is a class-string — a concrete class or a `Contracts` interface — never an arbitrary
  string identifier. This is not an untyped key/value registry.
- The template binding is the gate: at every call site the analyzer checks that the service satisfies
  the key, so `set(service: $connection, id: SqlConnection::class)` fails to type-check when the
  connection does not implement `SqlConnection`.
- `get(class-string<T>): T` is unchanged, so a consumer that resolves `SqlConnection::class` receives
  `SqlConnection`, not the concrete class behind it.
- The default keeps the previous behaviour for the call sites that already existed — the kernel's own
  `Environment` and `Diagnostics`, and the asset package's `AssetsConfig` — so this is one mechanism
  with an explicit key, not a second mechanism beside the first.
- `Kernel::service()` takes the same optional key, because the composition root is where a contract
  binding is declared.
- A registration whose service does not satisfy its key throws `ServiceKeyMismatch` naming both. The
  analyzer already prevents it in analysed code; the runtime guard makes the same failure loud when
  the call is built dynamically, rather than handing a caller an object of the wrong type. There is no
  `@phpstan-ignore` anywhere in this change.

## Rejected alternatives

| Alternative | Why not |
|---|---|
| A second method, `bind(string $id, object $service)`, beside `set()` | Two registration paths is exactly the "one way to do a thing" the contract forbids; a reader would have to decide which one applies. The key belongs in the one method that already registers a service. |
| Register under every interface the service implements | Implicit: the keys would be invisible to a reader of the composition root, and the container would silently acquire a key space no one declared. |
| Key by an arbitrary string identifier such as `'db.connection'` | An untyped registry: the analyzer can no longer tell a caller what it receives. |
| Make services implement an interface that declares their keys | Moves a composition-root decision into the service, and every contract-bound service would carry container vocabulary. |
| Reflection-based resolution by interface | Banned outright: resolution must be statically traceable. |
| Remove `set(object)` and require an explicit key at every call site | A breaking change for no gain — the default *is* the explicit key, spelled by the language as `$service::class`. |

## Consequences

- A package's provider can declare a collaborator under its `Contracts` interface and resolve it by
  that interface, so the `Contracts`/`Internal` split is enforceable at the composition root rather
  than only by convention. `iniznet/mahout-db` adopts this in the same change and its ADR-0004 is
  corrected: the connection is still read where WordPress keeps it, because a provider is instantiated
  by class name with no constructor arguments, but it is now declared under `SqlConnection` instead of
  under the connection's implementation class.
- `ids()` lists exactly the keys the composition root declared: a service declared by contract
  contributes one id, not two.
- The asset package's `set($service)` and `get(AssetsConfig::class)` call sites are unchanged and
  keep working; no consumer has to migrate to gain the capability.
