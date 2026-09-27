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
 * The identity deliberately does not carry the site's table prefix. The prefix is
 * a database fact, read by the package that owns the database; the identity is a
 * host fact, declared in the composition root. Requiring both at construction
 * would make the host read the database to build the value the database needs to
 * open itself. They meet in {@see self::assertFits()}, in the package that
 * composes the names, which is also where MySQL's 64-character identifier limit
 * is asserted against the widest name that will ever be built.
 *
 * A host slug legal to the scaffold generator runs to 60 characters, so a legal
 * slug can overflow that limit; refusing at boot, carrying the arithmetic and the
 * remedy, is what keeps the overflow from surfacing as an illegal identifier
 * thrown from inside a migration on a site that has data in it.
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
     * @param string $identity the host's own slug, declared once, in the composition root
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
     * are not SQL identifiers, so MySQL's limit does not apply to them and they
     * are not held to the budget the table names are.
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
