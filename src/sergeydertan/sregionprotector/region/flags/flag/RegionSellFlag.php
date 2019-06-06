<?php
declare(strict_types=1);

namespace sergeydertan\sregionprotector\region\flags\flag;

final class RegionSellFlag extends RegionFlag
{
    /**
     * @var int
     */
    public $price;

    public function __construct(bool $state = false, int $price = 0)
    {
        parent::__construct($state);
        $this->price = $price;
    }

    public function getPrice(): int
    {
        return $this->price;
    }

    public function setPrice(int $price): void
    {
        $this->price = $price;
    }
}
