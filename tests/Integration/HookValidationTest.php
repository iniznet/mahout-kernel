<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Kernel\Tests\Integration;

use Iniznet\Mahout\Kernel\Diagnostics;
use Iniznet\Mahout\Kernel\Environment;
use Iniznet\Mahout\Kernel\Hooks;
use Iniznet\Mahout\Kernel\Level;
use Iniznet\Mahout\Kernel\Record;
use Iniznet\Mahout\Kernel\Tests\Fixtures\InMemoryQuerySource;
use Iniznet\Mahout\Kernel\Tests\TestCase;

/**
 * Deliverable 5: an unknown hook warns in development mode and records nothing
 * when wp_is_development_mode( 'theme' ) is false.
 *
 * @internal
 */
final class HookValidationTest extends TestCase
{
    protected function tearDown(): void
    {
        remove_all_actions(Hooks::ALL);
        parent::tearDown();
    }

    public function testAnUnknownHookWarnsInDevelopmentMode(): void
    {
        $diagnostics = new Diagnostics(
            environment: $this->developmentEnvironment(),
            queries: new InMemoryQuerySource(),
        );

        $diagnostics->watchHooks([Hooks::BEFORE_BOOT]);

        do_action('mahout/kernel/not_a_declared_hook');

        $warnings = array_values(array_filter(
            $diagnostics->records(),
            static fn (Record $record): bool => Level::Warning === $record->level,
        ));

        self::assertCount(1, $warnings);
        self::assertSame('unknown hook', $warnings[0]->message);
        self::assertSame('mahout/kernel/not_a_declared_hook', $warnings[0]->context['hook']);
    }

    public function testAKnownHookWarnsNot(): void
    {
        $diagnostics = new Diagnostics(
            environment: $this->developmentEnvironment(),
            queries: new InMemoryQuerySource(),
        );

        $diagnostics->watchHooks([Hooks::BEFORE_BOOT]);

        do_action(Hooks::BEFORE_BOOT);

        self::assertSame([], $diagnostics->records());
    }

    public function testNothingIsWatchedWhenDevelopmentModeIsFalse(): void
    {
        $diagnostics = new Diagnostics(
            environment: new Environment(type: 'production', debug: false, developmentMode: false),
            queries: new InMemoryQuerySource(),
        );

        $diagnostics->watchHooks([Hooks::BEFORE_BOOT]);

        do_action('mahout/kernel/not_a_declared_hook');

        self::assertSame([], $diagnostics->records());
    }
}
