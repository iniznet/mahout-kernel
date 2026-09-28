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

- The generated hook reference is now two documents — `docs/reference/actions.md` and
  `docs/reference/filters.md`, replacing `docs/reference/hooks.md`. A single mixed table
  asked the reader to filter rows for the question they actually came with, which hooks
  fire and forget versus which hooks return a value, and that distinction is already
  recorded on every constant's docblock. `composer hooks:check` gates both files, and a
  package that declares none of one kind still carries the other document, so the gate
  cannot quietly stop running. Adopted from `iniznet/mahout-devtools` 2.0.1, whose
  `hooks:check` and `hooks:generate` take `--outdir=docs/reference`; the canonical command
  text lives in that package's gate manifest, and this repository's scripts are compared
  against it by `composer config:check`.
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
