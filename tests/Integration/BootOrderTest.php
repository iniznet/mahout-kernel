<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Kernel\Tests\Integration;

use Iniznet\Mahout\Kernel\Contracts\QuerySource;
use Iniznet\Mahout\Kernel\Diagnostics;
use Iniznet\Mahout\Kernel\Environment;
use Iniznet\Mahout\Kernel\Exception\KernelAlreadyBooted;
use Iniznet\Mahout\Kernel\Hooks;
use Iniznet\Mahout\Kernel\Kernel;
use Iniznet\Mahout\Kernel\Tests\Fixtures\BootLog;
use Iniznet\Mahout\Kernel\Tests\Fixtures\ContractBoundProvider;
use Iniznet\Mahout\Kernel\Tests\Fixtures\InMemoryQuerySource;
use Iniznet\Mahout\Kernel\Tests\Fixtures\LateProvider;
use Iniznet\Mahout\Kernel\Tests\Fixtures\RecordingBootFailureResponder;
use Iniznet\Mahout\Kernel\Tests\Fixtures\RecordingModule;
use Iniznet\Mahout\Kernel\Tests\Fixtures\RecordingProvider;
use Iniznet\Mahout\Kernel\Tests\TestCase;

/**
 * Deliverable 1: the boot sequence is ordered and observable.
 *
 * @internal
 */
final class BootOrderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        BootLog::reset();
    }

    protected function tearDown(): void
    {
        remove_all_actions(Hooks::BEFORE_BOOT);
        remove_all_actions(Hooks::AFTER_BOOT);
        remove_all_filters(Hooks::PROVIDERS);
        remove_all_filters(Hooks::MODULES);
        parent::tearDown();
    }

    public function testTheBootSequenceIsOrdered(): void
    {
        add_action(Hooks::BEFORE_BOOT, static function (): void {
            BootLog::record('before_boot');
        });
        add_action(Hooks::AFTER_BOOT, static function (): void {
            BootLog::record('after_boot');
        });

        $kernel = $this->kernel();
        $kernel->provider(RecordingProvider::class);
        $kernel->module(RecordingModule::class);

        $kernel->boot();

        self::assertSame(
            [
                'before_boot',
                'provider:register',
                'module:register',
                'provider:boot',
                'module:boot',
                'after_boot',
            ],
            BootLog::$events,
        );

        self::assertTrue($kernel->booted());
        self::assertTrue($kernel->services()->has(Environment::class));
        self::assertTrue($kernel->services()->has(Diagnostics::class));
    }

    public function testAServiceDeclaredUnderAContractsInterfaceResolvesByIt(): void
    {
        $kernel = $this->kernel();
        $kernel->provider(ContractBoundProvider::class);

        $kernel->boot();

        self::assertContains('contract:'.InMemoryQuerySource::class, BootLog::$events);
        self::assertTrue($kernel->services()->has(QuerySource::class));
        self::assertFalse($kernel->services()->has(InMemoryQuerySource::class));
    }

    public function testTheProvidersFilterAppendsAProvider(): void
    {
        add_filter(Hooks::PROVIDERS, static function (array $providers): array {
            return [...$providers, LateProvider::class];
        });

        $this->kernel()->boot();

        self::assertContains('late:register', BootLog::$events);
        self::assertContains('late:boot', BootLog::$events);
        self::assertNotContains('before_boot', BootLog::$events);
    }

    public function testASecondBootFailsLoudly(): void
    {
        $kernel = $this->kernel();
        $kernel->boot();

        $this->expectException(KernelAlreadyBooted::class);
        $kernel->boot();
    }

    private function kernel(): Kernel
    {
        return new Kernel(
            environment: new Environment(type: 'production', debug: false, developmentMode: false),
            diagnostics: $this->diagnostics(),
            bootFailures: new RecordingBootFailureResponder(),
        );
    }
}
