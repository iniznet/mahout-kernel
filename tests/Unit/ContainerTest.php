<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Kernel\Tests\Unit;

use Iniznet\Mahout\Kernel\Container;
use Iniznet\Mahout\Kernel\Contracts\QuerySource;
use Iniznet\Mahout\Kernel\Diagnostics;
use Iniznet\Mahout\Kernel\Environment;
use Iniznet\Mahout\Kernel\Exception\ServiceKeyMismatch;
use Iniznet\Mahout\Kernel\Exception\ServiceNotFound;
use Iniznet\Mahout\Kernel\Tests\Fixtures\InMemoryQuerySource;
use Iniznet\Mahout\Kernel\Tests\TestCase;

/**
 * @internal
 */
final class ContainerTest extends TestCase
{
    public function testAResolutionReturnsTheDeclaredConcreteType(): void
    {
        $container = new Container();
        $environment = new Environment(type: 'production', debug: false, developmentMode: false);

        $container->set($environment);

        self::assertTrue($container->has(Environment::class));
        self::assertSame($environment, $container->get(Environment::class));
        self::assertSame([Environment::class], $container->ids());
    }

    public function testACollaboratorResolvesByTheContractsInterfaceItWasDeclaredUnder(): void
    {
        $container = new Container();
        $queries = new InMemoryQuerySource();

        $container->set(service: $queries, id: QuerySource::class);

        self::assertTrue($container->has(QuerySource::class));
        self::assertSame($queries, $container->get(QuerySource::class));
        self::assertSame([QuerySource::class], $container->ids());

        // One declaration names one key. The concrete class is not a second key
        // the composition root never declared.
        self::assertFalse($container->has(InMemoryQuerySource::class));
    }

    public function testResolvingADeclaredCollaboratorWithoutDeclaringItFailsLoudly(): void
    {
        $container = new Container();

        $this->expectException(ServiceNotFound::class);
        $this->expectExceptionMessage('was not declared in the container');

        try {
            $container->get(Environment::class);
        } catch (ServiceNotFound $exception) {
            self::assertSame(Environment::class, $exception->serviceId());

            throw $exception;
        }
    }

    public function testRegisteringAServiceUnderAKeyItDoesNotSatisfyFailsLoudly(): void
    {
        $container = new Container();
        $key = QuerySource::class;

        $this->expectException(ServiceKeyMismatch::class);
        $this->expectExceptionMessage('was registered under the key');

        try {
            // A Diagnostics is not a QuerySource; a container that accepted this
            // would hand a caller an object of the wrong type.
            $container->set(service: $this->diagnostics(), id: $key);
        } catch (ServiceKeyMismatch $exception) {
            self::assertSame(QuerySource::class, $exception->key());
            self::assertSame(Diagnostics::class, $exception->service());

            throw $exception;
        }
    }
}
