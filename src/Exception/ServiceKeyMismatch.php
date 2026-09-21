<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Kernel\Exception;

/**
 * A service was registered under a key it does not satisfy. The key and the
 * service are both named, because the composition root meant one of the two and
 * the failure is silent until a caller receives an object of the wrong type.
 */
final class ServiceKeyMismatch extends \LogicException implements MahoutException
{
    private function __construct(
        string $message,
        private readonly string $key,
        private readonly string $service,
    ) {
        parent::__construct($message);
    }

    public static function between(string $key, string $service): self
    {
        return new self(
            sprintf('The service "%s" was registered under the key "%s", which it does not satisfy.', $service, $key),
            $key,
            $service,
        );
    }

    /** The key the service was registered under. */
    public function key(): string
    {
        return $this->key;
    }

    /** The concrete class of the service that was registered. */
    public function service(): string
    {
        return $this->service;
    }
}
