<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Kernel\Exception;

/**
 * A service was resolved that the composition root never declared. The lookup
 * is explicit, so the failure is loud rather than an empty result.
 */
final class ServiceNotFound extends \OutOfBoundsException implements MahoutException
{
    private function __construct(string $message, private readonly string $serviceId)
    {
        parent::__construct($message);
    }

    public static function forId(string $id): self
    {
        return new self(sprintf('Service "%s" was not declared in the container.', $id), $id);
    }

    public function serviceId(): string
    {
        return $this->serviceId;
    }
}
