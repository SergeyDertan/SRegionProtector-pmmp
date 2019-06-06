<?php
declare(strict_types=1);

namespace sergeydertan\sregionprotector\util;

final class Pair
{
    private $first, $second;

    public function __construct($first, $second)
    {
        $this->first = $first;
        $this->second = $second;
    }

    public function getFirst()
    {
        return $this->first;
    }

    public function getSecond()
    {
        return $this->second;
    }
}
