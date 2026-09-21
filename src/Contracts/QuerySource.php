<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Kernel\Contracts;

/**
 * The query facts diagnostics reads.
 *
 * It is a contract rather than a direct \$wpdb read so a query budget is
 * provable with no WordPress, and so the one place that touches \$wpdb is
 * named.
 */
interface QuerySource
{
    /** The total queries made so far, read from wpdb's own counter. */
    public function count(): int;

    /**
     * The SQL text of the queries made so far.
     *
     * Empty unless the site enabled SAVEQUERIES; the caller decides whether
     * the environment should read it at all.
     *
     * @return list<string>
     */
    public function statements(): array;
}
