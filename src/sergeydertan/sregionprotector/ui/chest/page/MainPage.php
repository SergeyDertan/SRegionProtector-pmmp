<?php
declare(strict_types=1);

namespace sergeydertan\sregionprotector\ui\chest\page;

use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\Player;
use sergeydertan\sregionprotector\messenger\Messenger;
use sergeydertan\sregionprotector\region\Region;

class MainPage extends Page
{
    /**
     * @var Item[]
     */
    private static $icons = [];

    /**
     * @param Region $region
     * @param int $page
     * @return Item[]
     */
    public function getItems(Region $region, int $page = 0): array
    {
        if (count(self::$icons) != count(parent::$pages)) {
            self::initIcons();
        }
        $items = self::$icons;

        $info = Item::get(ItemIds::EMERALD_BLOCK)->setCustomName(Messenger::getInstance()->getMessage("gui.main.region-description-item", ["@region"], [$region->getName()]));
        $info->setLore([
            Messenger::getInstance()->getMessage("gui.main.region-description",
                [
                    "@level",
                    "@creator",
                    "@priority",
                    "@size"
                ],
                [
                    $region->getLevel(),
                    $region->getCreator(),
                    (string)$region->getPriority(),
                    (string)$region->getSize()
                ]
            )
        ]);
        $items[0] = $info;
        $this->prepareItem($info, 0);
        return $items;
    }

    private function initIcons(): void
    {
        self::$icons = [];
        $i = 0;
        foreach (parent::$pages as $page) {
            if ($page instanceof MainPage) continue;
            self::$icons[++$i] = $page->getIcon();
        }
        parent::prepareItems(self::$icons);
    }

    public function getIcon(): Item
    {
        return null;
    }

    public function getName(): ?string
    {
        return "main";
    }

    public function hasPermission(Player $player, Region $region): bool
    {
        return false;
    }
}
