<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Kernel;

/**
 * One Diagnostics entry: level, a stable message, structured context and the
 * support reference that ties the record to what a user was shown.
 *
 * @internal use Diagnostics::records() to read records
 */
final readonly class Record
{
    /**
     * @param array<string, string|int|float|bool|\Throwable|\UnitEnum|null> $context
     */
    public function __construct(
        public Level $level,
        public string $message,
        public array $context,
        public string $reference,
    ) {
    }
}
