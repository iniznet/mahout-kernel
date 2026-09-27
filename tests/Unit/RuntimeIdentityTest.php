<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Kernel\Tests\Unit;

use Iniznet\Mahout\Kernel\Exception\InvalidRuntimeIdentity;
use Iniznet\Mahout\Kernel\RuntimeIdentity;
use Iniznet\Mahout\Kernel\Tests\TestCase;

/**
 * The runtime identity's invariant: the identity is a host fact, the prefix is a
 * database fact, and the budget is asserted where the two meet.
 *
 * The numbers here are measured, not chosen. MySQL caps an identifier at 64
 * characters; the widest name this family composes is
 * {prefix}mahout_{identity}_field_values; and a host slug legal to the scaffold
 * generator is legal up to 60 characters, which overflows that by a wide margin.
 * The failure carries the arithmetic, because a limit without the allowance leaves
 * a developer measuring by hand.
 *
 * @internal
 */
final class RuntimeIdentityTest extends TestCase
{
    private const string PREFIX = 'wp_';

    /**
     * The namespace names the host, and the class inside it does not: two roots that
     * share a namespace derive the same identity, so renaming `Bootstrap` to
     * `Root` cannot move a site's storage.
     */
    public function testTheIdentityComesFromTheNamespaceAndNotTheClassName(): void
    {
        $fromKernel = RuntimeIdentity::fromClass(\Iniznet\Mahout\Kernel\Kernel::class);
        $fromDiagnostics = RuntimeIdentity::fromClass(\Iniznet\Mahout\Kernel\Diagnostics::class);

        self::assertSame('kernel', $fromKernel->value);
        self::assertSame($fromKernel->value, $fromDiagnostics->value);
        self::assertSame('mahout_kernel_db_schema_version', $fromKernel->namespacedName('db_schema_version'));
    }

    public function testASecondNamespaceDerivesASecondIdentity(): void
    {
        self::assertSame('tests', RuntimeIdentity::fromClass(TestCase::class)->value);
    }

    /**
     * A skin fact is refused by construction: this type has no path to the active
     * stylesheet or the plugin directory, so the only way in is the host's own name.
     */
    public function testANamespacelessRootHasNothingToDerive(): void
    {
        try {
            RuntimeIdentity::fromClass('Bootstrap');
            self::fail('a root with no namespace must be refused rather than guessed at');
        } catch (InvalidRuntimeIdentity $failure) {
            self::assertStringContainsString('carries no namespace', $failure->getMessage());
            self::assertStringContainsString('fromSlug', $failure->remedy());
        }
    }

    public function testTheWidestComposedNameIsWhatTheTableCallsFor(): void
    {
        $identity = RuntimeIdentity::fromSlug('howdah');

        self::assertSame('howdah', $identity->value);
        self::assertSame('wp_mahout_howdah_field_values', $identity->tableName(self::PREFIX, 'field_values'));
        self::assertSame('wp_mahout_howdah_migrations', $identity->tableName(self::PREFIX, 'migrations'));
    }

    public function testOptionNamesCarryTheIdentityWithoutTheTablePrefix(): void
    {
        $identity = RuntimeIdentity::fromSlug('howdah');

        self::assertSame('mahout_howdah_db_schema_version', $identity->namespacedName('db_schema_version'));
        self::assertSame('mahout_howdah_sweep_cursors', $identity->namespacedName('sweep_cursors'));
    }

    public function testAnIdentityOfThirtyNineCharactersFitsTheDefaultPrefix(): void
    {
        $identity = RuntimeIdentity::fromSlug(\str_repeat('a', 39));
        $identity->assertFits(self::PREFIX);

        self::assertSame(62, \strlen($identity->tableName(self::PREFIX, RuntimeIdentity::LONGEST_IDENTIFIER_SUFFIX)));
    }

    public function testTheAllowanceIsTheIdentityLengthThatExactlyFillsTheLimit(): void
    {
        $identity = RuntimeIdentity::fromSlug(\str_repeat('b', 41));
        $identity->assertFits(self::PREFIX);

        self::assertSame(41, $identity->allowance(self::PREFIX));
        self::assertSame(64, \strlen($identity->tableName(self::PREFIX, RuntimeIdentity::LONGEST_IDENTIFIER_SUFFIX)));
    }

    /**
     * One character over the allowance is where an unguarded install used to fail:
     * inside a migration, from the identifier type, with no remedy in the message.
     */
    public function testTheBudgetIsRefusedWithTheArithmeticAndARemedy(): void
    {
        $identity = RuntimeIdentity::fromSlug(\str_repeat('c', 42));

        try {
            $identity->assertFits(self::PREFIX);
            self::fail('an identity whose widest name overflows an identifier must be refused before any name is built');
        } catch (InvalidRuntimeIdentity $failure) {
            self::assertStringContainsString(\str_repeat('c', 42), $failure->getMessage());
            self::assertStringContainsString('wp_mahout_'.\str_repeat('c', 42).'_field_values', $failure->getMessage());
            self::assertStringContainsString('65 characters', $failure->getMessage());
            self::assertStringContainsString('64-character limit', $failure->getMessage());
            self::assertStringContainsString('may be 41 characters long', $failure->getMessage());
            self::assertStringContainsString('Shorten the identity', $failure->remedy());
            self::assertSame(\str_repeat('c', 42), $failure->identity());
        }
    }

    /**
     * The budget is not a property of the identity alone: the same slug that fits a
     * site on wp_ does not fit one on a longer prefix. That is why the prefix is an
     * argument to the assertion rather than a fact the identity carries, and why a
     * host can declare its name without reading the database.
     */
    public function testTheBudgetShrinksWithTheTablePrefix(): void
    {
        $identity = RuntimeIdentity::fromSlug(\str_repeat('d', 39));

        $identity->assertFits(self::PREFIX);

        try {
            $identity->assertFits('wptests_');
            self::fail('a longer table prefix must consume the same budget');
        } catch (InvalidRuntimeIdentity $failure) {
            self::assertStringContainsString('may be 36 characters long', $failure->getMessage());
            self::assertStringContainsString('Shorten the identity to at most 36 characters', $failure->remedy());
            self::assertSame(36, $identity->allowance('wptests_'));
        }
    }

    public function testAnUnderscoredIdentityIsAcceptedAsDeclared(): void
    {
        self::assertSame('office_suite', RuntimeIdentity::fromSlug('office_suite')->value);
    }

    /**
     * @dataProvider unsafeIdentities
     */
    public function testAnIdentityThatCannotSitInAnIdentifierIsRefused(string $identity): void
    {
        try {
            RuntimeIdentity::fromSlug($identity);
            self::fail(sprintf('"%s" must not be accepted as a runtime identity', $identity));
        } catch (InvalidRuntimeIdentity $failure) {
            self::assertStringContainsString('cannot be used in a SQL identifier', $failure->getMessage());
            self::assertStringContainsString('underscores in place of hyphens', $failure->remedy());
            self::assertSame($identity, $failure->identity());
        }
    }

    /**
     * @return array<string, array{string}>
     */
    public static function unsafeIdentities(): array
    {
        return [
            'hyphenated host slug' => ['office-suite'],
            'leading underscore' => ['_office'],
            'leading digit' => ['9lives'],
            'uppercase' => ['Howdah'],
            'space' => ['my office'],
            'empty' => [''],
            'punctuation' => ['office.site'],
        ];
    }
}
