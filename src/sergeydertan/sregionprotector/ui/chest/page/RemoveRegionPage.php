<?php
declare(strict_types=1);

namespace sergeydertan\sregionprotector\ui\chest\page;

use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\Player;
use sergeydertan\sregionprotector\main\SRegionProtectorMain;
use sergeydertan\sregionprotector\messenger\Messenger;
use sergeydertan\sregionprotector\region\Region;
use sergeydertan\sregionprotector\region\RegionManager;
use sergeydertan\sregionprotector\util\Tags;

class RemoveRegionPage extends Page
{
    /**
     * @var RegionManager
     */
    private $regionManager;

    public function __construct()
    {
        $this->regionManager = SRegionProtectorMain::getInstance()->getRegionManager();
    }

    /**
     * @param Region $region
     * @param int $page
     * @return Item[]
     */
    public function getItems(Region $region, int $page = 0): array
    {
        $items = [];

        $no = Item::get(ItemIds::EMERALD_BLOCK)->setCustomName(Messenger::getInstance()->getMessage("gui.remove.cancel"));
        $nbt = $no->getNamedTag();
        $nbt->setString(Tags::OPEN_PAGE_TAG, self::$mainPage->getName());
        $no->setNamedTag($nbt);
        $items[12] = $no;

        $yes = Item::get(ItemIds::REDSTONE_BLOCK)->setCustomName(Messenger::getInstance()->getMessage("gui.remove.apply"));
        $nbt = $yes->getNamedTag();
        $nbt->setByte(Tags::REMOVE_REGION_TAG, 1);
        $yes->setNamedTag($nbt);
        $items[14] = $yes;

        $this->prepareItems($items);

        return $items;
    }

    public function handle(Item $item, Region $region, Player $player): bool
    {
        if ($item->getNamedTagEntry(Tags::REMOVE_REGION_TAG) !== null && $this->hasPermission($player, $region) && $this->regionManager->regionExists($region->getName())) {
            $this->regionManager->removeRegion($region);
            return true;
        }
        return false;
    }

    public function hasPermission(Player $player, Region $region): bool
    {
        return $player->hasPermission("sregionprotector.admin") || $region->isCreator($player->getName());
    }

    public function getIcon(): Item
    {
        $icon = Item::get(ItemIds::TNT)->setCustomName(Messenger::getInstance()->getMessage("gui.main.go-to-remove"));
        $nbt = $icon->getNamedTag();
        $nbt->setString(Tags::OPEN_PAGE_TAG, $this->getName());
        $icon->setNamedTag($nbt);
        return $icon;
    }

    public function getName(): ?string
    {
        return "remove-region";
    }
}
