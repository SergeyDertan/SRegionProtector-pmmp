<?php
declare(strict_types=1);

namespace sergeydertan\sregionprotector\ui\chest\page;

use pocketmine\block\BlockIds;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\nbt\tag\IntTag;
use pocketmine\Player;
use sergeydertan\sregionprotector\messenger\Messenger;
use sergeydertan\sregionprotector\region\flags\RegionFlags;
use sergeydertan\sregionprotector\region\Region;
use sergeydertan\sregionprotector\ui\chest\page\flags\Flag;
use sergeydertan\sregionprotector\ui\chest\page\flags\FlagList;
use sergeydertan\sregionprotector\util\Tags;

class FlagsPage extends Page
{
    /**
     * @var int[]
     */
    private static $flagToBlock = [];

    public static function initIcons(): void
    {
        self::$flagToBlock = array_fill(0, RegionFlags::FLAG_AMOUNT, ItemIds::BANNER);

        self::$flagToBlock[RegionFlags::FLAG_PLACE] = BlockIds::GRASS;
        self::$flagToBlock[RegionFlags::FLAG_BREAK] = BlockIds::GRASS;
        self::$flagToBlock[RegionFlags::FLAG_USE] = BlockIds::LEVER;
        self::$flagToBlock[RegionFlags::FLAG_PVP] = ItemIds::DIAMOND_SWORD;
        self::$flagToBlock[RegionFlags::FLAG_EXPLODE] = BlockIds::TNT;
        self::$flagToBlock[RegionFlags::FLAG_EXPLODE_BLOCK_BREAK] = BlockIds::TNT;
        self::$flagToBlock[RegionFlags::FLAG_LIGHTER] = ItemIds::FLINT_AND_STEEL;
        self::$flagToBlock[RegionFlags::FLAG_LEAVES_DECAY] = BlockIds::LEAVES;
        self::$flagToBlock[RegionFlags::FLAG_ITEM_DROP] = ItemIds::STICK;
        self::$flagToBlock[RegionFlags::FLAG_MOB_SPAWN] = ItemIds::SPAWN_EGG;
        self::$flagToBlock[RegionFlags::FLAG_CROPS_DESTROY] = BlockIds::FARMLAND;
        self::$flagToBlock[RegionFlags::FLAG_REDSTONE] = ItemIds::REDSTONE_DUST;
        self::$flagToBlock[RegionFlags::FLAG_ENDER_PEARL] = ItemIds::ENDER_PEARL;
        self::$flagToBlock[RegionFlags::FLAG_FIRE] = BlockIds::FIRE;
        self::$flagToBlock[RegionFlags::FLAG_LIQUID_FLOW] = BlockIds::STILL_WATER;
        self::$flagToBlock[RegionFlags::FLAG_CHEST_ACCESS] = BlockIds::CHEST;
        self::$flagToBlock[RegionFlags::FLAG_SLEEP] = ItemIds::BED;
        self::$flagToBlock[RegionFlags::FLAG_SMART_DOORS] = ItemIds::IRON_DOOR;
        self::$flagToBlock[RegionFlags::FLAG_MINEFARM] = BlockIds::DIAMOND_ORE;
        self::$flagToBlock[RegionFlags::FLAG_POTION_LAUNCH] = ItemIds::SPLASH_POTION;
        self::$flagToBlock[RegionFlags::FLAG_HEAL] = ItemIds::GOLDEN_APPLE;
        self::$flagToBlock[RegionFlags::FLAG_NETHER_PORTAL] = ItemIds::NETHER_REACTOR;
        self::$flagToBlock[RegionFlags::FLAG_SELL] = ItemIds::EMERALD;
        self::$flagToBlock[RegionFlags::FLAG_FRAME_ITEM_DROP] = ItemIds::ITEM_FRAME;
        self::$flagToBlock[RegionFlags::FLAG_BUCKET_EMPTY] = ItemIds::BUCKET;
        self::$flagToBlock[RegionFlags::FLAG_BUCKET_FILL] = ItemIds::BUCKET;
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

        $flags = array_slice((new FlagList($region))->load(true)->getFlags(), $page * 18, 18);
        /**
         * @var Flag[] $flags
         */
        foreach ($flags as $flag) {
            $flagId = $flag->getId();
            $item = Item::get(self::$flagToBlock[$flagId], $flagId === RegionFlags::FLAG_BUCKET_FILL ? 8 : 0);

            $item->setCustomName($flag->getName());

            $lore = [];
            $lore[] = "Value: " . ($flag->getState() === RegionFlags::getStateFromString("allow", $flagId) ? "allow" : "deny");
            if ($flagId === RegionFlags::FLAG_SELL) {
                $lore[] = "Price: " . $flag->getFlag()->getPrice();
            } else if ($flagId === RegionFlags::FLAG_TELEPORT) {
                $pos = $flag->getFlag()->getPosition();
                if ($pos !== null) {
                    $lore[] = "x: " . round($pos->x) . ", y: " . round($pos->y) . ", z: " . round($pos->z);
                }
            }
            $item->setLore($lore);
            $nbt = $item->getNamedTag();
            $nbt->setInt(Tags::FLAG_ID_TAG, $flagId);
            $list[++$counter] = $item;
        }
        $this->prepareItems($list, $page);
        return $list;
    }

    public function getIcon(): Item
    {
        $icon = Item::get(ItemIds::BANNER)->setCustomName(Messenger::getInstance()->getMessage("gui.main.go-to-flags"));
        $nbt = $icon->getNamedTag();
        $nbt->setString(Tags::OPEN_PAGE_TAG, $this->getName());
        $icon->setNamedTag($nbt);
        return $icon;
    }

    public function getName(): ?string
    {
        return "flags";
    }

    public function handle(Item $item, Region $region, Player $player): bool
    {
        if (!$this->hasPermission($player, $region)) return false;
        /**
         * @var IntTag $tag
         */
        $tag = $item->getNamedTagEntry(Tags::FLAG_ID_TAG);
        if (!$tag instanceof IntTag) return false;
        $flagId = $tag->getValue();
        if ($flagId === RegionFlags::FLAG_SELL || $flagId === RegionFlags::FLAG_TELEPORT) return false;
        if (!RegionFlags::hasFlagPermission($player, $flagId)) return false;
        $region->setFlagState($flagId, !$region->getFlagState($flagId));
        return true;
    }

    public function hasPermission(Player $player, Region $region): bool
    {
        return $player->hasPermission("sregionprotector.admin") || $region->isOwner($player->getName(), true);
    }
}
