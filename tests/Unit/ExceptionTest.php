<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Kernel\Tests\Unit;

use Iniznet\Mahout\Kernel\Container;
use Iniznet\Mahout\Kernel\Exception\InvalidHookPayload;
use Iniznet\Mahout\Kernel\Exception\InvalidRuntimeIdentity;
use Iniznet\Mahout\Kernel\Exception\KernelAlreadyBooted;
use Iniznet\Mahout\Kernel\Exception\KernelBootFailed;
use Iniznet\Mahout\Kernel\Exception\MahoutException;
use Iniznet\Mahout\Kernel\Exception\RuntimeIdentityNotDeclared;
use Iniznet\Mahout\Kernel\Exception\SecondCompositionRoot;
use Iniznet\Mahout\Kernel\Exception\ServiceKeyMismatch;
use Iniznet\Mahout\Kernel\Exception\ServiceNotFound;
use Iniznet\Mahout\Kernel\Exception\SpanNotStarted;
use Iniznet\Mahout\Kernel\Kernel;
use Iniznet\Mahout\Kernel\Tests\TestCase;

/**
 * @internal
 */
final class ExceptionTest extends TestCase
{
    public function testEveryNamedConstructorBuildsAMarkedException(): void
    {
        $previous = new \RuntimeException('root');

        $exceptions = [
            KernelBootFailed::because($previous),
            KernelBootFailed::withoutDatabase(),
            KernelAlreadyBooted::secondCall(),
            ServiceNotFound::forId('App\\Service'),
            ServiceKeyMismatch::between('App\\Contracts\\Service', 'App\\Service'),
            SpanNotStarted::forName('surface'),
            InvalidHookPayload::notAList('mahout/kernel/providers'),
            InvalidHookPayload::notAClass('mahout/kernel/providers'),
            InvalidHookPayload::notAProvider('mahout/kernel/providers'),
            InvalidHookPayload::notAModule('mahout/kernel/modules'),
            SecondCompositionRoot::after(Kernel::class, Container::class),
            InvalidRuntimeIdentity::notIdentifierSafe('office-suite'),
            InvalidRuntimeIdentity::nothingToDerive('Bootstrap'),
            InvalidRuntimeIdentity::overBudget('a_very_long_identity_indeed', 'wp_mahout_a_very_long_identity_indeed_field_values', 41),
            RuntimeIdentityNotDeclared::forPackage(Kernel::class),
        ];

        foreach ($exceptions as $exception) {
            self::assertInstanceOf(MahoutException::class, $exception);
            self::assertNotSame('', $exception->getMessage());
        }

        self::assertSame($previous, KernelBootFailed::because($previous)->getPrevious());
        self::assertSame('App\\Service', ServiceNotFound::forId('App\\Service')->serviceId());
        self::assertSame('App\\Contracts\\Service', ServiceKeyMismatch::between('App\\Contracts\\Service', 'App\\Service')->key());
        self::assertSame('App\\Service', ServiceKeyMismatch::between('App\\Contracts\\Service', 'App\\Service')->service());
    }
}
