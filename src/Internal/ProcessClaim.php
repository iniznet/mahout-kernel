<?php

/**
 * The claim that this process has one composition root.
 *
 * Two hosts installing the same libraries collide in ways none of them can see.
 * Composer registers each host's autoloader by prepending, so the host that loads
 * last — in WordPress, the theme, which is included after every active plugin —
 * answers every `Iniznet\Mahout\*` name for both, and the other host's pinned copy
 * never runs at all. Meanwhile the schema-version option is one fixed key, the field
 * tables are named from the table prefix, and the hook namespace is the packages',
 * so both hosts write to the same state while each believes it owns it. Nothing
 * throws. That is why this is refused at the boundary that both hosts must pass
 * through, rather than left to whoever notices a field reading back null.
 *
 * This holds no service and resolves nothing: it records one class name and refuses
 * a second. `NoStaticServiceAccessRule` governs the classes that resolve
 * collaborators, and the name says which side of that line this is on.
 *
 * @internal
 */

declare(strict_types=1);

namespace Iniznet\Mahout\Kernel\Internal;

use Iniznet\Mahout\Kernel\Exception\SecondCompositionRoot;

final class ProcessClaim
{
    /** @var class-string|null */
    private static ?string $root = null;

    private function __construct()
    {
    }

    /**
     * Claim this process for one root, or refuse a different one.
     *
     * Reclaiming by the same root is idempotent: a request may build its kernel
     * more than once, and a process that has already said who owns it does not need
     * to be told again.
     *
     * @param class-string $root
     *
     * @throws SecondCompositionRoot when another root holds the claim
     */
    public static function claim(string $root): void
    {
        if (null !== self::$root && self::$root !== $root) {
            throw SecondCompositionRoot::after(self::$root, $root);
        }

        self::$root = $root;
    }

    /**
     * The root that owns this process, or null while nobody has claimed it.
     */
    public static function claimedBy(): ?string
    {
        return self::$root;
    }

    /**
     * Hand the process back to nobody.
     *
     * The seam exists because a test process boots more than one fixture, and a
     * claim that could not be released would make the second fixture untestable —
     * which is the opposite of what it is for. No production path calls it: a
     * request that wants to change its own owner has already lost.
     */
    public static function release(): void
    {
        self::$root = null;
    }
}
