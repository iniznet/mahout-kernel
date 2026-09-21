<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Kernel\Exception;

/**
 * A mahout/kernel/providers or mahout/kernel/modules filter returned data the
 * kernel cannot register. The boot stops; it never skips the bad entry.
 */
final class InvalidHookPayload extends \UnexpectedValueException implements MahoutException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function notAList(string $hook): self
    {
        return new self(sprintf('The %s filter must return a list of class names.', $hook));
    }

    public static function notAClass(string $hook): self
    {
        return new self(sprintf('The %s filter returned a value that is not an existing class.', $hook));
    }

    public static function notAProvider(string $hook): self
    {
        return new self(sprintf('The %s filter returned a class that is not a ServiceProvider.', $hook));
    }

    public static function notAModule(string $hook): self
    {
        return new self(sprintf('The %s filter returned a class that is not a Module.', $hook));
    }
}
