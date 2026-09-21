<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Kernel;

use Iniznet\Mahout\Kernel\Exception\ServiceNotFound;

/**
 * The explicit service registry.
 *
 * There is no reflection and no autowiring: a service is resolvable only
 * because the composition root declared it. get() is a typed lookup, so a
 * caller that resolves a collaborator receives its concrete type or a loud
 * failure, never an object it must downcast.
 */
final class Container
{
    /** @var array<class-string, object> */
    private array $services = [];

    public function set(object $service): void
    {
        $this->services[$service::class] = $service;
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
     * The declared services, in declaration order. This is the enumerable
     * declaration of the graph.
     *
     * @return list<class-string>
     */
    public function ids(): array
    {
        return \array_keys($this->services);
    }
}
