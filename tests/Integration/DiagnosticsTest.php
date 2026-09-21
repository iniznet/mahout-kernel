<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Kernel\Tests\Integration;

use Iniznet\Mahout\Kernel\Diagnostics;
use Iniznet\Mahout\Kernel\Environment;
use Iniznet\Mahout\Kernel\Internal\WpdbQuerySource;
use Iniznet\Mahout\Kernel\Level;
use Iniznet\Mahout\Kernel\Tests\Fixtures\InMemoryQuerySource;
use Iniznet\Mahout\Kernel\Tests\TestCase;

/**
 * Deliverable 4: query counting, spans, typed records and reset isolation.
 *
 * @internal
 */
final class DiagnosticsTest extends TestCase
{
    public function testQueryCountMatchesWpdbOverFiveQueries(): void
    {
        $wpdb = $GLOBALS['wpdb'];
        $diagnostics = new Diagnostics(
            environment: new Environment(type: 'development', debug: true, developmentMode: false),
            queries: new WpdbQuerySource($wpdb),
        );

        $before = (int) $wpdb->num_queries;

        for ($index = 0; $index < 5; ++$index) {
            $wpdb->get_var('SELECT 1');
        }

        self::assertSame(5, (int) $wpdb->num_queries - $before);
        self::assertSame((int) $wpdb->num_queries, $diagnostics->queryCount());
    }

    public function testASpanReportsElapsedTimeAndQueryDelta(): void
    {
        $source = new InMemoryQuerySource();
        $diagnostics = $this->diagnostics(queries: $source);

        $diagnostics->start('surface');
        $source->record('SELECT 1');
        $source->record('SELECT 2');
        $source->record('SELECT 3');
        $diagnostics->stop('surface');

        self::assertSame(3, $diagnostics->queryCountSince('surface'));

        $records = $diagnostics->records();
        self::assertCount(1, $records);

        $span = $records[0];
        self::assertSame(Level::Debug, $span->level);
        self::assertSame('surface', $span->context['span']);
        self::assertSame(3, $span->context['queries']);
        self::assertIsFloat($span->context['seconds']);
        self::assertGreaterThanOrEqual(0.0, $span->context['seconds']);
    }

    public function testRecordsAreTypedAndTailableByCursor(): void
    {
        $diagnostics = $this->diagnostics();

        $reference = $diagnostics->log(Level::Info, 'booted', ['provider' => 'ThemeProvider']);

        self::assertSame('MH-0001', $reference);

        $cursor = \count($diagnostics->records());
        $diagnostics->log(Level::Info, 'second');

        $tailed = $diagnostics->since($cursor);
        self::assertCount(1, $tailed);
        self::assertSame('second', $tailed[0]->message);
        self::assertSame('MH-0002', $tailed[0]->reference);
    }

    public function testResetIsolatesOneRequestFromTheNext(): void
    {
        $source = new InMemoryQuerySource();
        $diagnostics = $this->diagnostics(queries: $source);

        $source->record('SELECT 1');
        $diagnostics->start('surface');
        $source->record('SELECT 2');
        $diagnostics->stop('surface');
        self::assertNotSame([], $diagnostics->records());

        $diagnostics->reset();

        self::assertSame([], $diagnostics->records());
        self::assertSame(0, $diagnostics->queryCountSince('surface'));
    }

    public function testTheQueryListIsEmptyOutsideDevelopment(): void
    {
        $source = new InMemoryQuerySource();
        $source->record('SELECT 1');

        $development = new Diagnostics(new Environment(type: 'development', debug: true, developmentMode: false), $source);
        self::assertSame(['SELECT 1'], $development->queries());

        $production = new Diagnostics(new Environment(type: 'production', debug: false, developmentMode: false), $source);
        self::assertSame([], $production->queries());
    }

    public function testStoppingASpanThatWasNeverStartedFailsLoudly(): void
    {
        $this->expectException(\Iniznet\Mahout\Kernel\Exception\SpanNotStarted::class);

        $this->diagnostics()->stop('never-started');
    }
}
