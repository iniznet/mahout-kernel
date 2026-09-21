# ADR-0002 — The container is the service registry; the theme builds a Services facade

Status: accepted

## Context

The architecture sketch writes `$kernel->services(): Services`, and then
`self::services()->diagnostics()`, `->environment()` and `->series()`. The
first two are kernel services; `series()` is a theme service. A kernel package
cannot name a theme's services, so it cannot ship that `Services` type.

## Decision

The kernel ships `Container`, an explicit declared registry, and
`Kernel::services(): Container`. The theme's composition root builds its own
`Services` facade over the container and exposes the typed accessors it owns.

The container is deliberately not a general-purpose resolver:

- `set(object)` registers a service under its own class name.
- `get(class-string<T>): T` is a typed lookup. There is no string identifier, no
  reflection and no autowiring.
- `get()` on an undeclared service throws `ServiceNotFound`.
- `Kernel::inWordPress()` is the kernel's composition-root named constructor,
  because the kernel needs `$wpdb` and the environment and the sketch's
  `new Kernel()` cannot supply them.

A fixture that resolves a collaborator by static class access rather than by
constructor injection fails `mahout.arch.noStaticServiceAccess`; the proof is in
`fixtures/architecture/violations/`.

## Consequences

- Every resolution is statically traceable: PHPStan infers the concrete type
  from the `class-string<T>` argument.
- The package does not depend on the theme, which keeps the dependency graph
  acyclic.
- A consumer that wants named accessors writes them in its own composition root,
  which is where the corpus puts wiring anyway.
