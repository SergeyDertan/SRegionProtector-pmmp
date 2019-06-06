<?php
declare(strict_types=1);

namespace sergeydertan\sregionprotector\region\chunk;

use sergeydertan\sregionprotector\region\Region;

final class Chunk
{
    private static $regionComparator = null;
    /**
     * @var float
     */
    private $x;
    /**
     * @var float
     */
    private $z;

    /**
     * @var Region[]
     */
    private $regions = [];

    public function __construct(float $x, float $z)
    {
        $this->x = $x;
        $this->z = $z;
        if (self::$regionComparator === null) {
            self::$regionComparator = function (Region $f, Region $s): int {
                return $s->getPriority() - $f->getPriority();
            };
        }
    }

    public function getX(): float
    {
        return $this->x;
    }

    public function getZ(): float
    {
        return $this->z;
    }

    /**
     * @return Region[]
     */
    public function getRegions(): array
    {
        return $this->regions;
    }

    public function addRegion(Region $region): void
    {
        $this->regions[] = $region;
        usort($this->regions, self::$regionComparator);
    }

    public function removeRegion(Region $region): void
    {
        unset($this->regions[array_search($region, $this->regions)]);
    }

    public function updatePriorities(): void
    {
        usort($this->regions, self::$regionComparator);
    }
}
