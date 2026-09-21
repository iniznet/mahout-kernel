<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Kernel;

/**
 * The environment facts the kernel reads once.
 *
 * It is a value object rather than a set of global reads so the development
 * branch of every predicate is provable without changing the running site.
 */
final readonly class Environment
{
    public function __construct(
        public string $type,
        public bool $debug,
        public bool $developmentMode,
    ) {
    }

    /**
     * The composition-root named constructor. It resolves no collaborator; it
     * reads the three WordPress facts and builds a value.
     */
    public static function fromWordPress(): self
    {
        return new self(
            type: wp_get_environment_type(),
            debug: WP_DEBUG,
            developmentMode: wp_is_development_mode('theme'),
        );
    }

    public function isProduction(): bool
    {
        return 'production' === $this->type;
    }

    /**
     * Whether a thrown failure should propagate with its trace rather than be
     * replaced by a defined error page.
     */
    public function exposesErrors(): bool
    {
        return $this->debug || \in_array($this->type, ['local', 'development'], true);
    }

    /**
     * The lowest level that reaches the server log.
     */
    public function logThreshold(): Level
    {
        if ($this->exposesErrors()) {
            return Level::Debug;
        }

        return 'staging' === $this->type ? Level::Warning : Level::Error;
    }
}
