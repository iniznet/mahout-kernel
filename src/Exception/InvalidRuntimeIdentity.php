<?php

/**
 * A host declared an identity its own packages cannot build safe names from.
 *
 * The condition is named rather than the call site because the fix belongs to the
 * installation: one declaration in the host's own metadata, not a change to any
 * package that happened to notice.
 */

declare(strict_types=1);

namespace Iniznet\Mahout\Kernel\Exception;

use Iniznet\Mahout\Kernel\RuntimeIdentity;

/**
 * The identity a host claims is carried by every option, table, query var and
 * REST field name the packages mint, so an identity that is illegal or that over
 *flows an identifier is a fact about the whole installation, discovered at boot
 * rather than in the middle of a migration on a site that has data in it.
 */
final class InvalidRuntimeIdentity extends \InvalidArgumentException implements MahoutException
{
    private function __construct(
        string $message,
        private readonly string $identity,
        private readonly string $remedy,
    ) {
        parent::__construct($message);
    }

    public static function notIdentifierSafe(string $identity): self
    {
        return new self(
            \sprintf(
                'The runtime identity "%s" cannot be used in a SQL identifier: it must start with a lowercase letter and contain lowercase letters, digits and underscores only.',
                $identity,
            ),
            $identity,
            'Declare an identity in lowercase with underscores in place of hyphens — "office_suite", not "office-suite" — and rename nothing that already has stored data under the old one.',
        );
    }

    /**
     * @param string $composed the longest name this identity would ever produce
     */
    public static function overBudget(string $identity, string $composed, int $allowance): self
    {
        return new self(
            \sprintf(
                'The runtime identity "%s" does not fit this site: the longest name a mahout package composes from it is "%s" at %d characters, against the %d-character limit MySQL imposes on an identifier. With this table prefix the identity may be %d characters long.',
                $identity,
                $composed,
                \strlen($composed),
                RuntimeIdentity::IDENTIFIER_LIMIT,
                $allowance,
            ),
            $identity,
            \sprintf('Shorten the identity to at most %d characters, or move the site to a shorter table prefix.', $allowance),
        );
    }

    public function identity(): string
    {
        return $this->identity;
    }

    /**
     * What the installation must do. A failure that names the limit without the
     * arithmetic leaves a developer measuring by hand.
     */
    public function remedy(): string
    {
        return $this->remedy;
    }
}
