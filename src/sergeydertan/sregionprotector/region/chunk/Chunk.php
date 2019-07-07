<?php
declare(strict_types=1);

namespace sergeydertan\sregionprotector\region\chunk;

use sergeydertan\sregionprotector\region\Region;

final class Chunk
{
    private static $regionComparator = null;
    /**
     * @var int
     */
    private $x;
    /**
     * @var int
     */
    private $z;

    /**
     * @var Region[]
     */
    private $regions = [];

    public function __construct(int $x, int $z)
    {
        $this->x = $x;
        $this->z = $z;
        if (self::$regionComparator === null) {
            self::$regionComparator = function (Region $f, Region $s): int {
                return $s->getPriority() - $f->getPriority();
            };
        }
    }

    public function getX(): int
    {
        return $this->x;
    }

    public function getZ(): int
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
        if (in_array($region, $this->regions)) return;
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
