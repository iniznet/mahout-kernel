# ADR-0001 — Package documents stand alone

Status: accepted

## Context

The canonical planning corpus lives in the `iniznet/howdah` repository under
`docs/planning/`. Every package repository excludes that directory through its
own `.gitignore`, because the corpus records rejected alternatives and business
assumptions that are not published with the code.

The corpus's own artifact contract asks a package README to link into that
directory, and the theme's `AGENTS.md` links into it throughout. A published
repository cannot contain either link: the target is absent from every clone.

## Decision

Nothing committed to this repository links into `docs/planning/`. This package's
`README.md` and `AGENTS.md` are written to stand alone:

- `README.md` states the package's concern, its public surface, an example, the
  compatibility guarantee and the licence without a corpus link.
- `AGENTS.md` reproduces the discipline contract in full. Its Reference section
  names each document by name and states that the corpus is private, rather than
  linking to it.

The package's own decisions live under `docs/decisions/` and are linked from
here.

## Consequences

- A reader who clones only this repository has the complete contract.
- A rule change must be applied to this document and to the corpus in the same
  coordinated change; the corpus is the source and this document is the copy.
- The same decision is recorded in `iniznet/mahout-devtools`; the two packages
  reach it independently and for the same reason.
