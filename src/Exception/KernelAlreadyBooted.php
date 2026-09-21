<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Kernel\Exception;

/**
 * boot() was called twice on one Kernel. It is a programmer error; a second
 * registration is exactly the failure the one-boot rule prevents.
 */
final class KernelAlreadyBooted extends \LogicException implements MahoutException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function secondCall(): self
    {
        return new self('The kernel has already booted; boot() runs once per request.');
    }
}
