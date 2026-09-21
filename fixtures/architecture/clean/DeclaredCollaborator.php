<?php

declare(strict_types=1);

namespace Fixture\Kernel\Clean;

final class SeriesRepository
{
    public function find(): string
    {
        return 'series';
    }
}

final class Surfaces
{
    public function __construct(private readonly SeriesRepository $series)
    {
    }

    public function render(): string
    {
        return $this->series->find();
    }
}
// EXPECT-NONE: the collaborator arrives through the constructor.
