<?php
declare(strict_types=1);

namespace sergeydertan\sregionprotector\ui\chest;

use pocketmine\inventory\ContainerInventory;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\types\WindowTypes;
use sergeydertan\sregionprotector\region\Region;

final class UIInventory extends ContainerInventory
{
    /**
     * @var Region
     */
    private $region;

    /**
     * UIInventory constructor.
     * @param Vector3 $holder
     * @param Item[] $items
     * @param Region $region
     */
    public function __construct(Vector3 $holder, array $items, Region $region)
    {
        parent::__construct($holder, $items, 27, $region->getName());
        $this->region = $region;
    }

    public function getDefaultSize(): int
    {
        return 27;
    }

    public function getNetworkType(): int
    {
        return WindowTypes::CONTAINER;
    }

    public function getName(): string
    {
        return $this->region->getName();
    }

    public function getRegion(): Region
    {
        return $this->region;
    }
}
