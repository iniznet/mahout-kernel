# ADR-0004 — A consumer has one shared PHPStan config, so arch and stan run the same analyser

Status: accepted

## Context

`iniznet/mahout-devtools` separates its own analysis into `phpstan-types.neon`
(no rules) and `phpstan-arch.neon` (the rules), and its `composer arch` runs the
second. A consumer cannot copy that split: the divergence gate requires the
consumer's root `phpstan.neon` to include the shared configuration, and the
shared configuration carries the rules. The divergence gate also rejects a root
`phpstan-arch.neon` outright.

## Decision

The consumer's `composer stan` and `composer arch` both run
`phpstan analyse -c phpstan.neon`. The architecture rules are proven by the
package's own fixture test (`tests/Architecture/ArchitectureRuleProofTest.php`),
which runs the shared rules over `fixtures/architecture` and asserts the
identifiers that must and must not fire.

## Consequences

- The gate-name set matches the shared gate list, so CI stays uniform.
- One analyser run covers types and architecture; there is no second config to
  drift.
- The negative proof is a test, not a green gate, which is where a fixture
  belongs: an expected violation must not make `composer check` red.
