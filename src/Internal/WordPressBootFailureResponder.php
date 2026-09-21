<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Kernel\Internal;

use Iniznet\Mahout\Kernel\Contracts\BootFailureResponder;
use Iniznet\Mahout\Kernel\Environment;

/**
 * The defined boot-failure output.
 *
 * Development rethrows the original failure so the trace is impossible to
 * miss. Production presents a translated message with status 500 instead of a
 * white screen. Neither path returns to the boot sequence.
 *
 * @internal
 */
final readonly class WordPressBootFailureResponder implements BootFailureResponder
{
    public function __construct(private Environment $environment)
    {
    }

    public function failed(\Throwable $failure): never
    {
        if ($this->environment->exposesErrors()) {
            throw $failure;
        }

        wp_die(
            esc_html__('The site could not start.', 'mahout-kernel'),
            '',
            ['response' => 500],
        );
    }
}
