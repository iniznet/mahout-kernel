<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Kernel\Tests\Fixtures;

use Iniznet\Mahout\Kernel\Contracts\QuerySource;

/**
 * A database-free QuerySource, so a query-budget proof needs no WordPress.
 *
 * @internal
 */
final class InMemoryQuerySource implements QuerySource
{
    /** @var list<string> */
    private array $statements = [];

    private int $count = 0;

    public function count(): int
    {
        return $this->count;
    }

    /** @return list<string> */
    public function statements(): array
    {
        return $this->statements;
    }

    public function record(string $sql): void
    {
        $this->statements[] = $sql;
        ++$this->count;
    }
}
