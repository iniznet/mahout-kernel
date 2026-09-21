<?php

declare(strict_types=1);

namespace Fixture\Kernel\Violations;

final class SeriesRepository
{
    public static function find(): string
    {
        return 'series';
    }
}

final class Surfaces
{
    public function render(): string
    {
        return SeriesRepository::find(); // EXPECT: mahout.arch.noStaticServiceAccess
    }
}
