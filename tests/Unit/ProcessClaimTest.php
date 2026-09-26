<?php

/**
 * One process, one composition root.
 *
 * The cases are the three things a claim has to get right: the first holder wins,
 * the same holder asking again is not a conflict, and a different holder is refused
 * with both names in the failure — because the remedy is a decision about the
 * installation, and a message naming only the second root cannot state it. The two
 * "roots" are stand-in class names: the claim records an identity and resolves
 * nothing, so no fixture object is needed to exercise it.
 *
 * @internal
 */
declare(strict_types=1);

namespace Iniznet\Mahout\Kernel\Tests\Unit;

use Iniznet\Mahout\Kernel\Container;
use Iniznet\Mahout\Kernel\Diagnostics;
use Iniznet\Mahout\Kernel\Exception\SecondCompositionRoot;
use Iniznet\Mahout\Kernel\Internal\ProcessClaim;
use Iniznet\Mahout\Kernel\Kernel;
use Iniznet\Mahout\Kernel\Tests\TestCase;

final class ProcessClaimTest extends TestCase
{
    /**
     * A claim that outlived its test would make the next fixture unclaimable: the
     * whole point of the class is that it is process-wide.
     */
    protected function tearDown(): void
    {
        ProcessClaim::release();

        parent::tearDown();
    }

    public function testTheFirstRootHoldsTheClaim(): void
    {
        self::assertNull(ProcessClaim::claimedBy(), 'nobody owns an untouched process.');

        ProcessClaim::claim(Kernel::class);

        self::assertSame(Kernel::class, ProcessClaim::claimedBy());
    }

    public function testTheSameRootClaimingAgainIsNotAConflict(): void
    {
        ProcessClaim::claim(Kernel::class);
        ProcessClaim::claim(Kernel::class);

        self::assertSame(Kernel::class, ProcessClaim::claimedBy(), 'a request that builds its kernel twice is not a second owner.');
    }

    public function testADifferentRootIsRefusedAndBothAreNamed(): void
    {
        ProcessClaim::claim(Kernel::class);

        try {
            ProcessClaim::claim(Container::class);
        } catch (SecondCompositionRoot $failure) {
            self::assertSame(Kernel::class, $failure->claimedBy());
            self::assertSame(Container::class, $failure->attemptedBy());
            self::assertStringContainsString('one root of record', $failure->getMessage());
            self::assertSame(Kernel::class, ProcessClaim::claimedBy(), 'a refused claim does not steal the process.');

            return;
        }

        self::fail('a second composition root must be refused.');
    }

    public function testReleaseReturnsTheProcessToNobodyAndLetsTheNextFixtureClaim(): void
    {
        ProcessClaim::claim(Kernel::class);

        ProcessClaim::release();

        self::assertNull(ProcessClaim::claimedBy());

        ProcessClaim::claim(Diagnostics::class);

        self::assertSame(Diagnostics::class, ProcessClaim::claimedBy(), 'the seam exists for a test process booting more than one fixture.');
    }
}
