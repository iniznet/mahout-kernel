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
