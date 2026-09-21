# Changelog

All notable changes to this package are recorded here, in Keep a Changelog
order. The format follows Semantic Versioning; a major entry names each removal.

## [Unreleased]

### Added

- The kernel: the single boot sequence, the explicit `Container`, the typed
  `Environment` value, `Diagnostics`, and the `mahout/kernel/*` hooks.
- The public `Contracts` surface: `ServiceProvider`, `Module`,
  `QuerySource` and `BootFailureResponder`.
- The architecture-rule proof fixtures for static service access and for
  `error_log()` confinement.

### Changed

- `Container::set()` takes an optional `class-string` key and registers the
  service under it, so a service can be declared and resolved by the `Contracts`
  interface a consumer depends on. The key defaults to the service's own class
  name, so existing `set($service)` call sites are unchanged and no consumer has
  to migrate. `Kernel::service()` takes the same key.
- `get()` returns the type its key declares: resolving `Contracts\SqlConnection`
  yields `SqlConnection`, not the concrete class behind it.

### Added

- `Exception\ServiceKeyMismatch`, thrown when a service is registered under a
  key it does not satisfy.

### Decisions

- 0006: a service is registered under the key it is resolved by. It amends
  ADR-0002 and leaves every other property of the container in place — explicit
  declarations, static traceability, no reflection, no autowiring, no hidden
  resolution.
