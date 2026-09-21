<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Kernel\Contracts;

/**
 * What happens when a provider or a module throws during boot.
 *
 * It returns never on purpose: development rethrows so the trace is visible,
 * production stops the request with a defined error page. Neither returns to
 * the boot sequence, so no half-registered graph is ever served.
 */
interface BootFailureResponder
{
    public function failed(\Throwable $failure): never;
}
