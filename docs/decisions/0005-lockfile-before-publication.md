# ADR-0005 — The committed lockfile is resolved through the uncommitted path repository

Status: superseded by `iniznet/mahout-devtools` ADR-0008

The decision below recorded the committed lockfile as resolved through the
uncommitted path repository. The family's repositories are published now, each
manifest declares its family requirements as committed VCS repositories, and
each lock resolves over them, so a fresh clone installs. `PathRepositoryCheck`
fails a lock that pins a `path` dist.

## Context

REP-11 forbids a `path` repository in a committed `composer.json`, and the
divergence gate enforces it. `iniznet/mahout-devtools` is not published, so the
committed manifest cannot resolve it from a published lane, yet the divergence
gate requires the committed `composer.lock` to pin it.

## Decision

Local development resolves the sibling checkout:

- `composer.dev.json` (git-ignored) adds a `path` repository pointing at
  `../mahout-devtools` with `symlink: true` and requires it at `@dev`.
- The package's `extra.branch-alias` maps `dev-main` to `1.0.x-dev`, so `@dev`
  and the committed `^1.0` name the same major.
- `composer.dev.lock` is generated from that manifest and is git-ignored.
- The committed `composer.lock` is the same resolution, so the divergence gate
  can prove the pinned version.

## Consequences

- The committed lock names a path distribution for `mahout-devtools` until the
  package is published. At publication the lock is regenerated from the VCS
  lane and this ADR is superseded.
- `composer install` in a clean clone of a published release uses the published
  lane; until then a contributor runs
  `COMPOSER=composer.dev.json composer install`.
