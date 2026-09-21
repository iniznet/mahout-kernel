<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Kernel\Tests\Integration;

use Iniznet\Mahout\Kernel\Environment;
use Iniznet\Mahout\Kernel\Hooks;
use Iniznet\Mahout\Kernel\Internal\WordPressBootFailureResponder;
use Iniznet\Mahout\Kernel\Kernel;
use Iniznet\Mahout\Kernel\Level;
use Iniznet\Mahout\Kernel\Record;
use Iniznet\Mahout\Kernel\Tests\Fixtures\BootLog;
use Iniznet\Mahout\Kernel\Tests\Fixtures\RecordingBootFailureResponder;
use Iniznet\Mahout\Kernel\Tests\Fixtures\ThrowingProvider;
use Iniznet\Mahout\Kernel\Tests\TestCase;

/**
 * Deliverable 3: a throwing provider fires boot_failed with the Throwable and
 * the Kernel, and the request stops with a defined output instead of continuing.
 *
 * @internal
 */
final class BootFailureTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        BootLog::reset();
    }

    protected function tearDown(): void
    {
        remove_all_actions(Hooks::BOOT_FAILED);
        parent::tearDown();
    }

    public function testAThrowingProviderFiresBootFailedWithTheThrowableAndTheKernel(): void
    {
        BootLog::$failure = new \RuntimeException('the provider exploded');

        $capturedFailure = null;
        $capturedKernel = null;
        add_action(Hooks::BOOT_FAILED, static function (\Throwable $failure, Kernel $kernel) use (&$capturedFailure, &$capturedKernel): void {
            $capturedFailure = $failure;
            $capturedKernel = $kernel;
        }, accepted_args: 2);

        $responder = new RecordingBootFailureResponder();
        $diagnostics = $this->diagnostics();
        $kernel = new Kernel(
            environment: new Environment(type: 'production', debug: false, developmentMode: false),
            diagnostics: $diagnostics,
            bootFailures: $responder,
        );
        $kernel->provider(ThrowingProvider::class);

        try {
            $kernel->boot();
            self::fail('boot() returned after a provider threw');
        } catch (\Throwable $failure) {
            self::assertSame(BootLog::$failure, $failure);
        }

        self::assertTrue($responder->called);
        self::assertSame(BootLog::$failure, $responder->failure);
        self::assertSame(BootLog::$failure, $capturedFailure);
        self::assertSame($kernel, $capturedKernel);
        self::assertFalse($kernel->booted());

        $critical = array_values(array_filter(
            $diagnostics->records(),
            static fn (Record $record): bool => Level::Critical === $record->level,
        ));
        self::assertCount(1, $critical);
        self::assertSame('kernel boot failed', $critical[0]->message);
    }

    public function testTheProductionResponderStopsTheRequestWithA500(): void
    {
        $captured = [];

        add_filter('wp_die_handler', static function () use (&$captured): callable {
            return static function (string $message, string $title, array $args) use (&$captured): void {
                $captured = ['message' => $message, 'args' => $args];

                throw new \RuntimeException('wp_die sentinel');
            };
        });

        try {
            (new WordPressBootFailureResponder(new Environment(type: 'production', debug: false, developmentMode: false)))
                ->failed(new \RuntimeException('the provider exploded'));

            self::fail('the production responder returned');
        } catch (\RuntimeException $failure) {
            self::assertSame('wp_die sentinel', $failure->getMessage());
        } finally {
            remove_all_filters('wp_die_handler');
        }

        self::assertSame(500, $captured['args']['response']);
        self::assertStringContainsString('could not start', (string) $captured['message']);
    }

    public function testTheDevelopmentResponderRethrowsTheOriginalTrace(): void
    {
        $original = new \RuntimeException('the provider exploded');

        try {
            (new WordPressBootFailureResponder(new Environment(type: 'development', debug: true, developmentMode: false)))
                ->failed($original);

            self::fail('the development responder returned');
        } catch (\Throwable $failure) {
            self::assertSame($original, $failure);
        }
    }
}
