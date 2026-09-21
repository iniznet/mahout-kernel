<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Kernel\Exception;

/**
 * The kernel could not be built or booted. It is not recoverable: the graph is
 * incomplete and no request may be served from it.
 */
final class KernelBootFailed extends \RuntimeException implements MahoutException
{
    private function __construct(string $message, ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }

    public static function because(\Throwable $failure): self
    {
        return new self('The kernel could not boot.', $failure);
    }

    public static function withoutDatabase(): self
    {
        return new self('The kernel was built outside WordPress; wpdb is not available.');
    }
}
