# AGENTS.md — the discipline contract

This file is the working contract for anyone — human or agent — writing code in
this repository. It is the shared mahout contract plus a short package section at
the end recording whether this package violates it anywhere and why.

**Stack:** PHP 8.4+ · WordPress 7.1+ · PHPUnit · PHPStan max · Psalm (taint) ·
Rector · PHP-CS-Fixer

---

## The six laws

1. **Explicit over implicit.** Every dependency, hook, registration and side
effect is greppable from the composition root.
2. **One way to do a thing.** No alternative paths kept "just in case".
3. **Fail fast and loud.** No silent fallback, no degraded mode, no
environment-dependent behaviour switches.
4. **Small public surface.** `Contracts` plus a documented handful of concrete
classes. Everything else is `@internal`.
5. **Tooling enforces what prose promises.** A gate existing only in a markdown
file does not exist.
6. **Layer neutrality.** The system is correct and complete with no object
cache, no page cache and no CDN, and gets faster as each is added — with no
configuration change and no code change. It never depends on a layer existing,
never caps a site because a layer appeared, and has no scale mode.

---

## What this is not

The system is a **modular monolith**. One process, one database, one deployable
artefact. Module boundaries are enforced by `Contracts`, `@internal` and the
architecture rules — never by a network.

Runtime distribution — services, an internal RPC layer, a message broker beyond
`wp_cron`, separately deployed module processes — is a non-goal. The package set
is a publishing and contribution model, not a deployment topology.

---

## Before you write code

1. **Where does this go?** Use the layers below. Do not improvise a directory.
2. **Will it be queried?** That decides `StorageTarget` at field declaration
   time (themes and the fields package only).
3. **Which hook emits this?** Hooks come from Providers and Modules only.
4. **Which editor writes this?** A `Table` field is written only through the
   field panel or the field REST route. A `Meta` field may also be written
   through `register_post_meta`.
5. **Who may see it?** That decides the Surface's `Cacheability`.

---

## Layers — where code goes

| Layer | Location | Owns | Must not |
|---|---|---|---|
| Domain | `app/Features/<Name>/`, `src/` domain classes | queries, repositories, schema, hooks, business rules | render HTML |
| Presentation | `app/Components/`, `app/Features/*/Components/` | rendering typed props to HTML | fetch data, touch globals, fire hooks |
| Composition | `app/Render/` | resolving a request to a Surface | contain domain rules |
| Infrastructure | `app/Providers/` | hook attachment, assets, REST, admin | contain domain rules |

```
Providers --> Modules --> Repositories --> Mapper --> Data (DTO)
                              |
                              v
Surfaces --> Components --> Data (DTO)
```

Arrows point one way only. A Component never imports a Repository. A Repository
never imports a Component.

### Vertical slices

A feature owns its whole stack:

```
app/Features/Series/
  SeriesModule.php          # registers hooks
  SeriesSchema.php          # CPT, taxonomies, field groups - declarative
  SeriesRepository.php      # the only place WP_Query appears
  SeriesMapper.php          # the only place WP_Post appears
  SeriesData.php            # readonly DTO
  Surfaces/  Components/
```

---

## The six-step feature recipe

1. Declare data in `<Feature>Schema.php`, with an explicit `StorageTarget` per
   field.
2. Register the module — one line in the composition root.
3. Write the query in `<Feature>Repository.php`. Prime caches here.
4. Map to a DTO in `<Feature>Mapper.php`.
5. Compose the page with a Surface and Components, each with its own markup
   file.
6. Test: unit test every component, integration test the repository and the
   Surface's query ceiling.

---

## Banned — these fail the build

| Banned | Why |
|---|---|
| `extract()` | Variables appear from nowhere |
| `meta_query` in a public API | No `meta_value` index; one join per clause |
| `posts_per_page => -1` | Unbounded cost |
| Raw hook-name strings | Bypasses the `Hooks` constants |
| `get_post_meta()` / `get_user_meta()` on a registered field | Fields are read through the field layer only |
| Components calling repositories | Breaks the layer contract |
| Components referencing `WP_*` types | Components must be testable without WordPress |
| `template_include` routing | WordPress owns resolution |
| Reflection, service locators, facades | Untraceable dependencies |
| Static access to a service — repository, field reader, field query builder, registry, container | A value constructor is not a service locator; inject the collaborator |
| `__get`, `__set`, `__call`, dynamic properties | Invisible to static analysis |
| Trait properties, or `$this->` from a trait calling undeclared members | Concealed dependencies |
| `new \Exception(...)` or a public exception constructor | Untraceable, message drift |
| `error_log()` outside `Diagnostics` | Production noise |
| Superglobals outside the request boundary | No request boundary |
| Inline `<script>` or `<style>` echo | CSP hygiene and cacheability |
| PHPStan baselines, `@phpstan-ignore` without a reason | Hides problems instead of fixing them |
| `mixed` where a union is expressible | Static analysis stops working |
| `START TRANSACTION`, `COMMIT`, `ROLLBACK` outside the db gateway | One owner for the transaction boundary |
| `wp_cache_flush()`, and any `wp_cache_flush_group()` outside the gated cache service | Core returns `false` when the backend reports no support |
| `setcookie()` or `setrawcookie()` | The theme sets no cookie |
| `WP_List_Table` subclasses, quick-edit or bulk-edit field writes | Neither carries the save lifecycle or a post lock |
| A canonical, `robots` or `description` meta tag, and any hand-set security header | Another party owns those |
| Reflection in production | Hides the fragment key's contents |
| A dispatch arm with no `Cacheability` declaration | The argument has no default; an undeclared arm fails a test |
| A nonce, per-user or per-role value in a `Shared` Surface | A shared cache would replay one visitor's token to another |
| A cache key whose parts are not enumerable from the site's content graph | A key space an anonymous visitor can invent is a cache they can fill |
| A cache header outside the one cache policy | One policy, one owner, one place |
| A vendor purge API call | The theme owns the seam and the client owns the endpoint |
| A `$wpdb` statement against a howdah table with neither a `LIMIT` nor a primary-key equality | An unbounded statement is a scan |
| A schema query such as `information_schema` on a request path | A schema fact is read once into a non-autoloaded option |
| `LIKE` with a leading wildcard over `post_title`, `post_excerpt` or `post_content` | Measured at 150× to 450× the indexed path |
| `sleep()`, `usleep()`, `set_time_limit()`, or a wait-for-lock loop on a request path | A waiting PHP worker is unavailable to every other request |
| A per-request log line, or query logging, in production | A cost that grows linearly with traffic |

---

## Conventions

### DTOs

`final readonly class`, promoted and fully typed, `list<T>` in docblocks, enums
for closed sets, value objects for domain scalars. No `__get`, no `ArrayAccess`,
no `toArray()`, no `JsonSerializable`. Mapping from `WP_Post` happens in a Mapper.

### Value objects

Enforce invariants with PHP 8.4 property hooks. Use `public private(set)` when a
value is externally readable but not externally writable. DTOs stay plain;
value objects validate.

### Exceptions

`final`, private constructor, static named constructors, extend the most
specific SPL exception, implement the package marker interface, carry typed
context getters. Name the condition, not the throw site.

### Shells and shapes

`final` by default. Interfaces named for the role with no `Interface` suffix.
`#[Override]` on every override. Named arguments for optional parameters. No
boolean flags.

---

## Storage

> `wp_postmeta` is a load-with-the-entity store, not a query store.

| Need | `StorageTarget` |
|---|---|
| Read with the entity, never filtered | `Meta` |
| Filtered, sorted, aggregated or counted | `Table` |
| Repeater, display-only | Meta, one row per leaf, keyed by address |
| Repeater, queried or unbounded | Table leaves table, indexed for the member query |
| Option-context field | `Meta` always |

`storage` is required on every field. No default. A repeater stores no
envelope: every leaf is one scalar at its address — the chain of positions
and member ids from the root (`tags.2`, `credits.0.role`,
`sections.0.blocks.1`). Repeaters nest up to three levels; a member declares
`Carried` storage, because the root's target stores every leaf. A queried
repeater is the developer's declaration, answered member-qualified
(`credits.role`) over the leaves table's index — slower is the developer's
choice, not the theme's.

A `Table` field cannot be bound as a block attribute. **Sensitive values** go in
neither target: constants or environment only.

---

## Hooks

Names are `public const` on a `Hooks` class. Never inline.

```
mahout/{package}/{event}      # library
howdah/{domain}/{event}       # theme
```

- Actions never return. Filters always return the first argument.
- Filters pass values and arrays, never mutable WordPress objects.
- Emit only from Providers and Modules.
- `wp_head` and `wp_footer` fire inside the `Document` component. Do not remove.

| Priority | Meaning |
|---|---|
| 5 | pre-empt |
| 10 | default |
| 20 | post-process |
| `PHP_INT_MAX` | enforcement |

New hooks are documented first, then added to the inventory. `composer
hooks:check` fails if the generated reference is stale.

---

## Cacheability and throughput

- Every Surface declares `Cacheability` and `FragmentScope`; an `Uncacheable` arm
  states its reason.
- No layer is required, and no layer changes the code. One path.
- The theme owns the purge seam; the client owns the endpoint. Invalidation
  emits a hook; no vendor API is called.
- Every statement is bounded. A sweep is chunked, resumable and runtime-capped,
  and never runs on a request path.
- Search is an index, not a scan; it falls back loudly when the index is absent.

---

## Performance

Required before mapping any result set:

```php
update_meta_cache('post', $ids);
update_object_term_cache($ids, $taxonomies);
```

Repository query defaults:

```php
'no_found_rows'          => true,
'update_post_meta_cache' => false,
'update_post_term_cache' => false,
'ignore_sticky_posts'    => true,
'fields'                 => 'ids',
```

Every new Surface gets a query-ceiling test. Options over ~1 KB are stored
`autoload='no'`.

---

## Static access

**Permitted:** named constructors and codecs that hold no state and resolve no
collaborator — `Slug::fromString()`, `Media::fromAttachmentId()`,
`Environment::fromWordPress()`, `Kernel::inWordPress()` — plus enums and
`*::class` constants.

**Banned:** static access to anything that queries, caches, mutates or resolves
a collaborator — repositories, field readers, field query builders, registries,
containers.

**Boundary and composition-root exceptions, and nothing else:** the theme's
`Request::fromSuperglobals()`, `Bootstrap::run()`, `Bootstrap::render()`,
`Bootstrap::services()`, `Surfaces::resolve()`, and this package's
`Kernel::inWordPress()`. The test is not "is it static" but "does it resolve a
collaborator".

---

## Errors and security

- **Failures are loud; output is defined.** A thrown exception is recorded
  through `Diagnostics` at `critical`. Development rethrows it. Production
  renders the Error Surface with status `500`. No silent fallback, no
  substituted data, no white screen. A boot provider or module that throws fires
  `mahout/kernel/boot_failed` with the `Throwable` and the `Kernel`, and the
  request stops.
- **Request input has one boundary.** Superglobals are read only inside the
  request adapter, injected through constructors. State-changing requests use
  POST, a verified nonce, and a capability check. A REST route without an
  explicit `permission_callback` fails the build.
- **Sanitize on write, escape on read.** Exactly one escape per output; double
  escaping is a defect.
- **User data does not live in a theme-owned table.** Anything that must survive
  a theme switch belongs in a plugin.

---

## Gates

```bash
composer format      # PHP-CS-Fixer, @PSR12 + @Symfony
composer stan        # PHPStan, max level, no baseline
composer psalm       # Psalm taint
composer arch        # the architecture rules (the shared PHPStan config)
composer rector      # Rector dry-run
composer test        # PHPUnit
composer hooks:check # generated hook reference is current
composer i18n:check  # generated POT is current
composer doctor      # installation assembly
composer config:check# analyzer config and artifact set are referenced, not copied
composer check       # all of the above
```

`composer check` must pass before every commit. No exceptions, no `--no-verify`.

---

## Required tests

| Must have a test |
|---|
| Every component's rendered output |
| Every value object's invariant, including rejection |
| Every exception's named constructor |
| Every repository query shape |
| Field round-trip on `Meta` and on `Table` |
| `meta` to `table` and `table` to `meta` migration |
| Every Surface's query ceiling |
| Every dispatch arm's declared `Cacheability` and `FragmentScope` |
| The layer-0 suite: every front-end test passing with no object cache, no page cache and no CDN |
| Byte-identical output for two anonymous visitors on a `Shared` Surface |
| Single-flight: concurrent misses on one key cause exactly one regeneration |
| The orphan sweep's constant statement count per chunk and strictly advancing cursor |
| The search index's presence and exact column list, and result-set parity |
| A malformed search term never reaching `AGAINST` |
| Index presence after migration |
| Zero orphans after a post delete |
| A Surface that throws produces the error Surface in production and rethrows in development |
| A user-scoped field's export and erase paths |
| Contrast of every declared `theme.json` token pairing |
| The kernel boot order, and `boot_failed` on a throwing provider |
| Query counting matching `$wpdb->num_queries`, and a span's query delta |
| An unknown hook producing a warning in development and nothing in production |

No coverage target. Coverage rewards testing getters.

---

## Simplicity rules

- If a class has one public method and one responsibility, that is correct.
- Four repeated lines of `render()` boilerplate are cheaper than a trait that
  breaks `__DIR__`.
- If `functions.php` grows past eight lines, the design is wrong.
- If the composition root needs a config file, the indirection is wrong.
- If you need `@phpstan-ignore`, you need a different design.
- If a name needs explaining at the call site, name it better.

---

## This package's section

`mahout-kernel` implements the contract above. It records the following, and
only the following, deviations. Each is an ADR, not an edit to the contract.

| Deviation | Why | ADR |
|---|---|---|
| The architecture sketch's `Kernel::services(): Services` becomes `Kernel::services(): Container` | A library cannot name a theme's services; the theme builds a `Services` facade over the kernel container | 0002 |
| The sketch's `new Kernel()` becomes `Kernel::inWordPress()` at the composition root | The kernel needs the environment and `$wpdb`, which the no-argument form cannot supply | 0002 |
| `Diagnostics::__construct(Environment)` gains a `QuerySource` collaborator | It keeps `$wpdb` behind one boundary and makes the query-count proof database-free | 0003 |
| `composer stan` and `composer arch` run the same shared PHPStan config | A consumer's root config must include the shared one, which already carries the rules | 0004 |
| `Container::set(object, ?string)` takes the key it registers under, defaulting to the service's own class name | A `Contracts` interface is the public API; a container that can only key by the concrete class forces a consumer to name an implementation it is not allowed to depend on. One method with an explicit key, not a second registration path | 0006 |

No other rule in this document is relaxed. In particular: no reflection, no
service locator reached for statically, no trait, no dynamic property, no
`error_log()` outside `Diagnostics`, no `mixed` in a public signature, and no
`@phpstan-ignore`.

---

## Reference

The canonical planning corpus records the reasoning, the rejected alternatives
and the delivery roadmap. It is private and is not published with this
repository, so this document stands alone on purpose. The package-scoped
decisions are under `docs/decisions/`; the generated hook reference is
`docs/reference/hooks.md`.
