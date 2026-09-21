<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Kernel\Tests\Contract;

use Iniznet\Mahout\Kernel\Internal\WpdbQuerySource;
use Iniznet\Mahout\Kernel\Tests\Fixtures\InMemoryQuerySource;
use Iniznet\Mahout\Kernel\Tests\TestCase;

/**
 * Every QuerySource implementation must pass the same conformance suite.
 *
 * @internal
 */
final class QuerySourceContractTest extends TestCase
{
    public function testAnInMemorySourceConforms(): void
    {
        $source = new InMemoryQuerySource();

        self::assertSame(0, $source->count());
        self::assertSame([], $source->statements());

        $source->record('SELECT 1');

        self::assertSame(1, $source->count());
        self::assertSame(['SELECT 1'], $source->statements());
    }

    public function testTheWpdbSourceConforms(): void
    {
        $wpdb = $GLOBALS['wpdb'];
        $source = new WpdbQuerySource($wpdb);

        self::assertSame((int) $wpdb->num_queries, $source->count());
        self::assertIsArray($source->statements());
    }
}
