<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Kernel;

use Iniznet\Mahout\Kernel\Exception\InvalidRuntimeIdentity;

/**
 * The identity a host claims for the global names its mahout packages mint.
 *
 * Every option and table this family creates on a site carries it, because two
 * mahout systems installed on one site would otherwise write the same rows under
 * the same keys and neither could tell.
 *
 * It is explicit for every host, including a host that expects to be alone. A
 * name with no host in it claims the whole site implicitly, and storage whose
 * shape depends on which neighbour happens to be installed is an environment
 * switch inside the schema.
 *
 * The value is validated against the worst name that will ever be composed from
 * it rather than against itself. The limit that matters is MySQL's 64 characters
 * per identifier, and the budget shrinks as the site's table prefix grows; a
 * legal host slug of 60 characters overflows it. Failing here, carrying the
 * arithmetic and the remedy, is what keeps that overflow from surfacing as an
 * InvalidIdentifier thrown from inside a migration on a site that has data in it.
 *
 * What a prefix is made of is not this type's condition: the db layer owns the
 * legality of a composed name and refuses an illegal one at declaration time.
 * This type refuses what a host declares.
 */
final readonly class RuntimeIdentity
{
    /** MySQL's limit for a table, column or index name. */
    public const int IDENTIFIER_LIMIT = 64;

    /**
     * The longest suffix any mahout package composes onto an identity to make a
     * SQL identifier: the field layer's stored-value table is the widest name this
     * family builds.
     *
     * A package that composes a longer one changes this constant in the same
     * change, and that package's own test proves the two agree — this one cannot,
     * because the dependency runs the other way.
     */
    public const string LONGEST_IDENTIFIER_SUFFIX = 'field_values';

    private const string NAMESPACE = 'mahout';

    private const string SEPARATOR = '_';

    private const string PATTERN = '/^[a-z][a-z0-9_]*$/';

    private function __construct(
        public string $value,
    ) {
    }

    /**
     * The identity derived from the host's own composition root —
     * `RuntimeIdentity::fromClass(self::class)` in `Bootstrap::run()`.
     *
     * This is the source that can be trusted, and the kernel already holds it:
     * {@see Internal\ProcessClaim} refuses a second distinct
     * root in one process, so two hosts cannot reach the same identity without one
     * of them having already failed to boot. The namespace is what names the host,
     * so the last namespace segment is used and `Bootstrap` may be renamed to
     * anything without moving a site's storage.
     *
     * What this must never be derived from is a WordPress skin fact — the active
     * stylesheet, a plugin's directory name, `home_url()`. Those change underneath a
     * running site: activate a child theme and `get_stylesheet()` moves from
     * `howdah` to `howdah_child`, the schema-version option then reads absent,
     * absent reads stored zero, and every migration becomes pending again on a site
     * full of data. An identity that can move because somebody changed a screen is
     * the environment switch, not the convenience.
     *
     * @param class-string $root
     */
    public static function fromClass(string $root): self
    {
        $segments = \explode('\\', $root);
        $owner = \strtolower(\trim((string) ($segments[\count($segments) - 2] ?? '')));

        if ('' === $owner) {
            throw InvalidRuntimeIdentity::nothingToDerive($root);
        }

        return self::fromSlug($owner);
    }

    /**
     * @param string $identity the host's own slug, one declaration in the host
     */
    public static function fromSlug(string $identity): self
    {
        if (1 !== \preg_match(self::PATTERN, $identity)) {
            throw InvalidRuntimeIdentity::notIdentifierSafe($identity);
        }

        return new self($identity);
    }

    /**
     * A table this family owns on the site: {prefix}mahout_{identity}_{suffix}.
     */
    public function tableName(string $tablePrefix, string $suffix): string
    {
        return $tablePrefix.self::NAMESPACE.self::SEPARATOR.$this->value.self::SEPARATOR.$suffix;
    }

    /**
     * An option or query var this family owns: mahout_{identity}_{suffix}. These
     * are not SQL identifiers, so MySQL's limit does not apply to them and they are
     * not held to the budget the table names are.
     */
    public function namespacedName(string $suffix): string
    {
        return self::NAMESPACE.self::SEPARATOR.$this->value.self::SEPARATOR.$suffix;
    }

    /**
     * Refuse a prefix under which the identity cannot carry this family's widest
     * name. Called by the package that composes the names, once, before any name
     * is built.
     */
    public function assertFits(string $tablePrefix): void
    {
        $worst = $this->tableName($tablePrefix, self::LONGEST_IDENTIFIER_SUFFIX);

        if (\strlen($worst) > self::IDENTIFIER_LIMIT) {
            throw InvalidRuntimeIdentity::overBudget($this->value, $worst, $this->allowance($tablePrefix));
        }
    }

    /**
     * How long the identity may be and still fit every name the packages compose
     * from it on a site with this prefix.
     */
    public function allowance(string $tablePrefix): int
    {
        return self::IDENTIFIER_LIMIT
            - \strlen($tablePrefix)
            - \strlen(self::NAMESPACE)
            - \strlen(self::LONGEST_IDENTIFIER_SUFFIX)
            - 2;
    }
}
