<?php

/**
 * A host booted a package that mints site-wide names without declaring its identity.
 *
 * The condition is named rather than the call site because there is no fallback to
 * discover: the declaration is one line in the host's own composition root.
 */

declare(strict_types=1);

namespace Iniznet\Mahout\Kernel\Exception;

use Iniznet\Mahout\Kernel\RuntimeIdentity;

/**
 * The names this family writes into a site — its ledger table, its option rows —
 * carry the host that owns them. Without that declaration two mahout systems on
 * one site would share one ledger and one option namespace, so the absence is
 * refused rather than guessed at: no default identity, and no unsuffixed name kept
 * available "just in case".
 */
final class RuntimeIdentityNotDeclared extends \LogicException implements MahoutException
{
    /**
     * @param class-string $package
     */
    private function __construct(
        string $message,
        private readonly string $package,
    ) {
        parent::__construct($message);
    }

    /**
     * @param class-string $package the package that needs the declaration
     */
    public static function forPackage(string $package): self
    {
        return new self(
            \sprintf(
                'This host declared no runtime identity, so %s cannot name the tables and options it owns. Declare it in the composition root, before the providers boot, using this host\'s own slug: $kernel->service(RuntimeIdentity::fromSlug(\'%s\'), id: RuntimeIdentity::class).',
                $package,
                'the-host-slug',
            ),
            $package,
        );
    }

    /**
     * @return class-string
     */
    public function package(): string
    {
        return $this->package;
    }

    /**
     * The declaration to add, in the form the composition root expects.
     */
    public function remedy(): string
    {
        return \sprintf(
            '$kernel->service(RuntimeIdentity::fromSlug(\'%s\'), id: %s::class) before $kernel->boot().',
            'the-host-slug',
            RuntimeIdentity::class,
        );
    }
}
