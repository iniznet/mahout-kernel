<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Kernel\Internal;

use Iniznet\Mahout\Kernel\Contracts\QuerySource;

/**
 * The one place the kernel touches \$wpdb.
 *
 * Core increments wpdb::$num_queries itself, so nothing is wrapped and nothing
 * is hooked. The query list is read only when the site enabled SAVEQUERIES.
 *
 * @internal
 */
final readonly class WpdbQuerySource implements QuerySource
{
    public function __construct(private \wpdb $wpdb)
    {
    }

    public function count(): int
    {
        return (int) $this->wpdb->num_queries;
    }

    /** @return list<string> */
    public function statements(): array
    {
        if (!\defined('SAVEQUERIES') || !\constant('SAVEQUERIES')) {
            return [];
        }

        $statements = [];
        foreach ($this->wpdb->queries as $query) {
            $statement = $query[0] ?? null;
            if (\is_string($statement)) {
                $statements[] = $statement;
            }
        }

        return $statements;
    }
}
