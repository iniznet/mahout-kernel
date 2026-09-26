<?php

/**
 * A second composition root tried to own this process.
 *
 * The condition is named rather than the call site because the fix belongs to the
 * installation, not to the line that noticed: exactly one host installs the
 * packages, and any other host consumes that runtime over hooks or REST.
 */

declare(strict_types=1);

namespace Iniznet\Mahout\Kernel\Exception;

/**
 * Two roots in one process share the schema-version option, the field tables and
 * the hook namespace, and whichever autoloader answers first silently serves its
 * own code to both. The second distinct claim is refused instead of merged.
 */
final class SecondCompositionRoot extends \LogicException implements MahoutException
{
    /**
     * @param class-string $claimedBy
     * @param class-string $attemptedBy
     */
    private function __construct(
        string $message,
        private readonly string $claimedBy,
        private readonly string $attemptedBy,
    ) {
        parent::__construct($message);
    }

    /**
     * @param class-string $claimedBy   the root that owns this process
     * @param class-string $attemptedBy the root that tried to boot as well
     */
    public static function after(string $claimedBy, string $attemptedBy): self
    {
        return new self(
            \sprintf(
                'The composition root "%s" owns this process, so "%s" cannot also boot. One process has one root of record: install the mahout packages in exactly one host and let every other host consume that runtime over hooks or REST.',
                $claimedBy,
                $attemptedBy,
            ),
            $claimedBy,
            $attemptedBy,
        );
    }

    /**
     * @return class-string
     */
    public function claimedBy(): string
    {
        return $this->claimedBy;
    }

    /**
     * @return class-string
     */
    public function attemptedBy(): string
    {
        return $this->attemptedBy;
    }
}
