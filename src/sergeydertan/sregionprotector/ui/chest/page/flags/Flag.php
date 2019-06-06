<?php
declare(strict_types=1);

namespace sergeydertan\sregionprotector\ui\chest\page\flags;

use sergeydertan\sregionprotector\region\flags\flag\RegionFlag;
use sergeydertan\sregionprotector\region\flags\flag\RegionSellFlag;
use sergeydertan\sregionprotector\region\flags\flag\RegionTeleportFlag;
use sergeydertan\sregionprotector\region\flags\RegionFlags;

class Flag
{
    /**
     * flag id
     * @var int
     */
    private $id;
    /**
     * @var boolean
     */
    private $state;
    /**
     * @var string
     */
    private $name;
    /**
     * @var RegionFlag|RegionSellFlag|RegionTeleportFlag
     */
    private $flag;

    public function __construct(int $id, RegionFlag $flag)
    {
        $this->id = $id;
        $this->state = $flag->state;
        $this->flag = $flag;
        $this->name = RegionFlags::getFlagName($id);
        $this->name = strtoupper(substr($this->name, 0, 1)) . substr($this->name, 1);
        $this->name = str_replace("-", " ", $this->name);
    }

    /**
     * @return RegionFlag|RegionSellFlag|RegionTeleportFlag
     */
    public function getFlag(): RegionFlag
    {
        return $this->flag;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getState(): bool
    {
        return $this->state;
    }
}
