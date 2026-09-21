<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Kernel;

/**
 * The two WordPress hook dispatchers. A hook is declared once, as one of these.
 *
 * It is an enum rather than a string so the generated hook reference and the
 * development-only validator agree on the closed set.
 */
enum HookKind: string
{
    case Action = 'action';
    case Filter = 'filter';
}
