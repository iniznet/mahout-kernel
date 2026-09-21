<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Kernel;

use Iniznet\Mahout\Kernel\Exception\ServiceKeyMismatch;
use Iniznet\Mahout\Kernel\Exception\ServiceNotFound;

/**
 * The explicit service registry.
 *
 * There is no reflection and no autowiring: a service is resolvable only
 * because the composition root declared it, under the key it is resolved by.
 * The key is a class-string — a concrete class or a Contracts interface — never
 * an arbitrary string, and get() is a typed lookup, so a caller that resolves a
 * collaborator receives the type its key declares or a loud failure, never an
 * object it must downcast.
 */
final class Container
{
    /** @var array<class-string, object> */
    private array $services = [];

    /**
     * Register a service under the key it is resolved by.
     *
     * The key defaults to the service's own class name. A composition root that
     * wants a consumer to depend on a Contracts interface registers the service
     * under that interface, and get() then returns the interface, so the
     * dependency is traceable from the consumer's constructor to the one line in
     * the composition root that declares it. Nothing is registered under a key
     * the caller did not name: there is no second key, and no lookup by concrete
     * class for a service declared by contract.
     *
     * @template T of object
     *
     * @param T                    $service
     * @param class-string<T>|null $id
     */
    public function set(object $service, ?string $id = null): void
    {
        $key = $id ?? $service::class;

        if (!$service instanceof $key) {
            throw ServiceKeyMismatch::between($key, $service::class);
        }

        $this->services[$key] = $service;
    }

    /** @param class-string $id */
    public function has(string $id): bool
    {
        return \array_key_exists($id, $this->services);
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $id
     *
     * @return T
     */
    public function get(string $id): object
    {
        /** @var T|null $service */
        $service = $this->services[$id] ?? null;

        if (null === $service) {
            throw ServiceNotFound::forId($id);
        }

        return $service;
    }

    /**
     * The declared services, in declaration order, by the key each was declared
     * under. This is the enumerable declaration of the graph.
     *
     * @return list<class-string>
     */
    public function ids(): array
    {
        return \array_keys($this->services);
    }
}
