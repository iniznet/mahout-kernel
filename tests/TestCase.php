<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Kernel\Tests;

use Iniznet\Mahout\Kernel\Diagnostics;
use Iniznet\Mahout\Kernel\Environment;
use Iniznet\Mahout\Kernel\Tests\Fixtures\InMemoryQuerySource;

/**
 * The base test case for this package.
 *
 * Core's WP_UnitTestCase already wraps each test in a transaction and provides
 * the factories. This class exists so every test extends one name and so the
 * Diagnostics helpers live in one place.
 *
 * @internal
 */
abstract class TestCase extends \WP_UnitTestCase
{
    /**
     * A Diagnostics with an in-memory query source and an explicit environment,
     * so no test depends on the running site's environment type.
     */
    protected function diagnostics(
        ?Environment $environment = null,
        ?InMemoryQuerySource $queries = null,
    ): Diagnostics {
        return new Diagnostics(
            environment: $environment ?? new Environment(type: 'production', debug: false, developmentMode: false),
            queries: $queries ?? new InMemoryQuerySource(),
        );
    }

    protected function developmentEnvironment(): Environment
    {
        return new Environment(type: 'development', debug: true, developmentMode: true);
    }
}
