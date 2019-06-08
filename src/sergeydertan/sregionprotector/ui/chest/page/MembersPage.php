<?php
declare(strict_types=1);

namespace sergeydertan\sregionprotector\ui\chest\page;

use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\nbt\tag\StringTag;
use pocketmine\Player;
use sergeydertan\sregionprotector\main\SRegionProtectorMain;
use sergeydertan\sregionprotector\messenger\Messenger;
use sergeydertan\sregionprotector\region\Region;
use sergeydertan\sregionprotector\region\RegionManager;
use sergeydertan\sregionprotector\util\Tags;

class MembersPage extends Page
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
        $list = self::$navigatorsCache;
        $counter = -1;

        foreach (array_slice($region->getMembers(), $page * 18, 18) as $member) {
            $item = Item::get(ItemIds::SKULL, 3)->setCustomName(
                Messenger::getInstance()->getMessage("gui.members.member-name", ["@member"], [$member])
            )->setLore(
                [Messenger::getInstance()->getMessage("gui.members.member-lore")]
            );
            $nbt = $item->getNamedTag();
            $nbt->setString(Tags::TARGET_NAME_TAG, $member);;
            $item->setNamedTag($nbt);
            $list[++$counter] = $item;
        }
        $this->prepareItems($list, $page);
        return $list;
    }

    public function handle(Item $item, Region $region, Player $player): bool
    {
        if (!$this->hasPermission($player, $region)) return false;
        /**
         * @var StringTag $target
         */
        $target = $item->getNamedTagEntry(Tags::TARGET_NAME_TAG);
        if (!$target instanceof StringTag) return false;
        $target = $target->getValue();
        if (strlen($target) === 0 || !$region->isMember($target)) return false;
        $this->regionManager->removeMember($region, $target);
        return true;
    }

    public function hasPermission(Player $player, Region $region): bool
    {
        return $player->hasPermission("sregionprotector.admin") || $region->isOwner($player->getName(), true);
    }

    public function getIcon(): Item
    {
        $icon = Item::get(ItemIds::SKULL, 3)->setCustomName(Messenger::getInstance()->getMessage("gui.main.go-to-members"));
        $nbt = $icon->getNamedTag();
        $nbt->setString(Tags::OPEN_PAGE_TAG, $this->getName());
        $icon->setNamedTag($nbt);
        return $icon;
    }

    public function getName(): ?string
    {
        return "members";
    }
}
