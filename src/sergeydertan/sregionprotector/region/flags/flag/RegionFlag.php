<?php
declare(strict_types=1);

namespace sergeydertan\sregionprotector\region\flags\flag;

class RegionFlag
{
    /**
     * @var boolean
     */
    public $state;

    /**
     * RegionFlag constructor.
     * @param bool $state
     */
    public function __construct(bool $state = false)
    {
        $this->state = $state;
    }

    public function getState(): bool
    {
        return $this->state;
    }

    public function setState(bool $state): void
    {
        $this->state = $state;
    }
}
