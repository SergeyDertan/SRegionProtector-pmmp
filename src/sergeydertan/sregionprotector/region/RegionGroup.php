<?php
declare(strict_types=1);

namespace sergeydertan\sregionprotector\region;

abstract class RegionGroup
{
    const CREATOR = 0;
    const OWNER = 1;
    const MEMBER = 2;

    private function __construct()
    {
    }
}
