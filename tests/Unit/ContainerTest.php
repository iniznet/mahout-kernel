<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Kernel\Tests\Unit;

use Iniznet\Mahout\Kernel\Container;
use Iniznet\Mahout\Kernel\Environment;
use Iniznet\Mahout\Kernel\Exception\ServiceNotFound;
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
}
