# ADR-0003 — Diagnostics takes a QuerySource, and the hook inventory supplies the kind

Status: accepted

## Context

`Diagnostics` must read `$wpdb->num_queries` and, in development, the query
list. Reading the global directly would make the type untestable without a
booted WordPress and would put the `$wpdb` dependency in two classes. The
sketch constructor is `__construct(Environment)`.

Separately, the developed hook validator attaches to core's `all` hook, whose
callback receives the hook name and its arguments but not whether the hook was
dispatched as an action or as a filter. The sketch's `hook(string, HookKind)`
cannot learn the kind from `all`.

## Decision

1. `Diagnostics` receives a `QuerySource` contract in addition to
   `Environment`. `Internal\WpdbQuerySource` is the one place the kernel
   touches `$wpdb`; a test uses an in-memory source, so the five-query proof
   needs no database.
2. The development-only validator compares the observed name against an
   inventory passed to `watchHooks()`. The `all` listener reports
   `HookKind::Action`; the declared kind is carried by the generated hook
   reference and by the constants themselves, and the validator's job is the
   unknown-name warning, not kind classification.
3. The warning context carries the hook name and a best-effort calling file
   from a debug backtrace, because the emitter's file is not part of the
   `all` arguments.

## Consequences

- The query-budget assertion is a unit-level, database-free proof.
- The log line and the record carry the same reference, and the context stays a
  typed array.
- The kind recorded for an unknown hook is nominal; a later slice may replace it
  with a kind-aware inventory when the hook reference is machine-readable.
