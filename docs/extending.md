# Extending

## A new provider

One class per provider, registered at the composition root and nowhere else:

1. Implement `Contracts\\ServiceProvider`: `register(Container)` declares
   services, `boot(Container)` attaches to core hooks.
2. Name it in the composition root's provider list, in dependency order — a
   provider that resolves another's binding registers after it.
3. A service a consumer depends on by contract is registered under that
   contract's interface, never under its concrete class alone.
4. Test the registration: fire the hook, observe the screen — never count
   closures.

## A new module

Modules are the domain lane: implement `Contracts\\Module`, name it in the
composition root after the providers it reads. A module registers domain
hooks and bootstraps domain state; it performs no infrastructure attachment.

## A new hook

Hook names are `public const` on the package's `Hooks` class — never an
inline string. Actions never return; filters always return the first
argument. The generated reference is `docs/reference/hooks.md`; a stale
committed copy fails the suite.

## Ordering rules the boot sequence enforces

- Providers register, then modules, then provider boot, then module boot.
- `after_boot` fires only when all four phases succeed.
- A failure at any phase records `critical`, fires `boot_failed` and stops
  the request — which is why a provider must never resolve a binding that is
  not yet declared.
