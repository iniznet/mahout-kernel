# ADR-0007 — One process, one composition root

Status: accepted

## Context

The packages are libraries, and a library does not ask what kind of host installed
it. That is correct as a boundary and incomplete as a safety story: a site can install
the family twice, in a theme and in a plugin, and nothing fails.

What actually happens has three parts, and only the first is visible.

**One copy of the code runs for both hosts.** Composer's generated bootstrap registers
the class loader with `->register(true)` — prepended — and WordPress includes active
plugins (`wp-settings.php`) before the theme's `functions.php`. The theme's loader is
therefore first in the chain and resolves every shared name, so the plugin's pinned
copies of the same classes are never loaded. If the plugin depends on an API that
exists only in its newer pin, the failure is `Call to undefined method`, raised at
whichever call site the plugin reaches first, in a file the theme never mentions.

**One namespace of persisted state has two owners.** The schema version is a single
option (`mahout_db_schema_version`), the migration ledger is one table named from the
prefix, the field value and leaf tables likewise, and the hook namespace is the
packages'. Each host reads `SchemaVersion::pending()` as `code !== stored`, so the host
whose code constant is lower is pending forever: `migrate()` returns before recording
when its own list has nothing pending, so the option never falls back to its number.
Its lazy run path then fires on every admin request — and the property its value object
states, that a request whose schema is current must not touch the database at all,
stops holding for that host permanently.

**Field identity is not checked across hosts.** Each host builds its own
`FieldRegistry`, so `DuplicateFieldId` — which is what would otherwise make two owners
of one `field_id` audible — cannot fire between them. One host writing
`series_tagline` to postmeta while the other reads it from the value table is not
refused by anything; it reads back null.

## Decision

`Kernel::inWordPress()` takes the identity of the root building it — `self::class`, at
every call site — and claims the process through `Internal\ProcessClaim` before reading
any WordPress fact. The first claim wins; the same root claiming again is idempotent, so
a host that builds its kernel twice is not a second owner; a different root raises
`SecondCompositionRoot`, naming both, before a provider or a module has attached a
single hook.

The identity is passed by the host rather than derived from Composer's runtime API on
purpose. `InstalledVersions` reports whichever host's `installed.php` was loaded last,
which in this exact scenario is the same value for both callers — it cannot distinguish
them, and a guard that reads the wrong thing is worse than no guard. An explicit
argument also fails closed: a host that does not name itself does not compile, and
`class-string` is checked statically, so there is no string to drift.

`ProcessClaim` resolves nothing and holds one class name. That keeps it on the permitted
side of `NoStaticServiceAccessRule`, whose test is whether a static call resolves a
collaborator, and it is why the class is not named a registry. `release()` exists for a
test process that boots more than one fixture; no production path calls it.

The runtime claim is the backstop. The primary gate is `doctor`'s
`CompositionRootCheck`, which finds every host under `wp-content` carrying
`vendor/iniznet/mahout-*` and fails when there is more than one, while the trees are
still movable.

## Consequences

The signature change is a contract change and is adopted in dependency order: this
package, then the two hosts that call it — `wp-content/themes/howdah` and the
`mahout-scaffold` base stub — in the same delivery. Nothing outside the family calls
it; a consumer that appears later follows the same procedure.

A site that wants a plugin to own the domain and a theme to own presentation still has
exactly one option: one of them is the root of record, and the other consumes the
first over hooks or REST. That is not a limitation worked around but the shape that
leaves one owner per namespace, and it is the shape the contract already argues for in
"anything that must survive a theme switch belongs in a plugin."

Two hosts each booting their own runtime is not supported, and is now refused rather
than merely discouraged. A deployment that genuinely needs it — a multi-vendor site,
say — is a different system from this one and should say so in a decision of its own
rather than discover the difference in production.
