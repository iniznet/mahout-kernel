<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Kernel\Exception;

/**
 * A span was stopped before it was started. Spans are named and paired, so an
 * unpaired stop is a programmer error, not a zero.
 */
final class SpanNotStarted extends \LogicException implements MahoutException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function forName(string $span): self
    {
        return new self(sprintf('Span "%s" was stopped before it was started.', $span));
    }
}
