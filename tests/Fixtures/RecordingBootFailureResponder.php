<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Kernel\Tests\Fixtures;

use Iniznet\Mahout\Kernel\Contracts\BootFailureResponder;

/**
 * @internal
 */
final class RecordingBootFailureResponder implements BootFailureResponder
{
    public bool $called = false;

    public ?\Throwable $failure = null;

    public function failed(\Throwable $failure): never
    {
        $this->called = true;
        $this->failure = $failure;

        throw $failure;
    }
}
