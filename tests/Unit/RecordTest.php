<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Kernel\Tests\Unit;

use Iniznet\Mahout\Kernel\Level;
use Iniznet\Mahout\Kernel\Record;
use Iniznet\Mahout\Kernel\Tests\TestCase;

/**
 * @internal
 */
final class RecordTest extends TestCase
{
    public function testARecordCarriesEveryField(): void
    {
        $record = new Record(
            level: Level::Critical,
            message: 'surface failed',
            context: ['kind' => 'singular', 'count' => 3],
            reference: 'MH-0001',
        );

        self::assertSame(Level::Critical, $record->level);
        self::assertSame('surface failed', $record->message);
        self::assertSame(['kind' => 'singular', 'count' => 3], $record->context);
        self::assertSame('MH-0001', $record->reference);
    }
}
