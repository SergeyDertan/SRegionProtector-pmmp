<?php declare(strict_types=1);

namespace sergeydertan\sregionprotector\ui\chest;

use pocketmine\block\Block;
use pocketmine\block\BlockIds;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\nbt\NetworkLittleEndianNBTStream;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\network\mcpe\protocol\BlockEntityDataPacket;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;
use pocketmine\Player;
use pocketmine\tile\Chest;
use pocketmine\tile\Tile;
use sergeydertan\sregionprotector\messenger\Messenger;
use sergeydertan\sregionprotector\region\Region;
use sergeydertan\sregionprotector\ui\chest\page\Page;
use sergeydertan\sregionprotector\ui\chest\page\RemoveRegionPage;
use sergeydertan\sregionprotector\util\Tags;

abstract class ChestUIManager
{
    /**
     * loader id -> UIInventory
     * @var UIInventory[]
     */
    private static $inventories;

    private function __construct()
    {
    }

    public static function open(Player $player, Region $region): void
    {
        $pos = self::sendFakeChest($player, $region->getName());
        $inventory = new UIInventory($pos, Page::$mainPage->getItems($region), $region);
        if ($player->addWindow($inventory) === -1) {
            self::removeChest($player, $pos);
        } else {
            self::$inventories[$player->getLoaderId()] = $inventory;
        }
    }

    private static function sendFakeChest(Player $target, string $region): Vector3
    {
        $pk1 = new UpdateBlockPacket();
        $pk1->x = (int)$target->x;
        $pk1->y = (int)$target->y - 1;
        $pk1->z = (int)$target->z;
        $pk1->flags = UpdateBlockPacket::FLAG_NONE;
        $pk1->blockRuntimeId = Block::get(BlockIds::CHEST, 0)->getRuntimeId();
        $target->dataPacket($pk1);

        $pk2 = new BlockEntityDataPacket();
        $pk2->x = $pk1->x;
        $pk2->y = $pk1->y;
        $pk2->z = $pk1->z;

        $nbt = new CompoundTag("", [
            new StringTag(Tile::TAG_ID, Chest::CHEST),
            new IntTag(Tile::TAG_X, $pk1->x),
            new IntTag(Tile::TAG_Y, $pk1->y),
            new IntTag(Tile::TAG_Z, $pk1->z)
        ]);
        $nbt->setString(Tags::CUSTOM_NAME_TAG, $region);
        $pk2->namedtag = (new NetworkLittleEndianNBTStream())->write($nbt);

        $target->dataPacket($pk2);

        return new Vector3($pk1->x, $pk1->y, $pk1->z);
    }

    public static function removeChest(Player $player, Vector3 $pos = null): void
    {
        if ($pos === null) {
            $inventory = self::$inventories[$player->getLoaderId()];
            if ($inventory !== null) {
                unset(self::$inventories[$player->getLoaderId()]);
                self::removeChest($player, $inventory->getHolder());
            }
            return;
        }
        $player->level->sendBlocks([$player], [$pos]);
    }

    public static function handle(Player $player, Item $item): void
    {
        if (!isset(self::$inventories[$player->getLoaderId()])) return;
        $inventory = self::$inventories[$player->getLoaderId()];
        $region = $inventory->getRegion();
        if (!$region->isLivesIn($player->getName()) && !$player->hasPermission("sregionprotector.info.other") && !$player->hasPermission("sregionprotector.admin")) {
            self::removeChest($player);
            return;
        }
        $nbt = $item->getNamedTag();
        if ($nbt->hasTag(Tags::CURRENT_PAGE_NAME_TAG)) {
            $page = Page::getPage($nbt->getString(Tags::CURRENT_PAGE_NAME_TAG));
            //navigators
            if ($page !== null) {
                if ($nbt->hasTag(Tags::REFRESH_PAGE_TAG)) {
                    $inventory->setContents($page->getItems($region, $nbt->getInt(Tags::CURRENT_PAGE_NUMBER_TAG)));
                    return;
                }
                if ($nbt->hasTag(Tags::NEXT_PAGE_TAG)) {
                    $pageNumber = $nbt->getInt(Tags::CURRENT_PAGE_NUMBER_TAG) + 1;
                    $inventory->setContents($page->getItems($region, $pageNumber));
                    return;
                }
                if ($nbt->hasTag(Tags::PREVIOUS_PAGE_TAG)) {
                    $pageNumber = $nbt->getInt(Tags::CURRENT_PAGE_NUMBER_TAG) - 1;
                    if ($pageNumber < 0) $pageNumber = 0;
                    $inventory->setContents($page->getItems($region, $pageNumber));
                    return;
                }
            }
        }
        if ($nbt->hasTag(Tags::OPEN_PAGE_TAG)) {
            //page link
            $page = Page::getPage($nbt->getString(Tags::OPEN_PAGE_TAG));
            if ($page !== null) {
                $inventory->setContents($page->getItems($region));
                return;
            }
        }
        if ($nbt->hasTag(Tags::CURRENT_PAGE_NAME_TAG)) {
            //page handler
            $page = Page::getPage($nbt->getString(Tags::CURRENT_PAGE_NAME_TAG));
            if ($page !== null) {
                if ($page->handle($item, $region, $player)) {
                    if ($page instanceof RemoveRegionPage) {
                        self::removeChest($player);
                        Messenger::getInstance()->sendMessage($player, "command.remove.region-removed", ["@region"], [$region->getName()]);
                        return;
                    }
                    $inventory->setContents($page->getItems($region, $nbt->getInt(Tags::CURRENT_PAGE_NUMBER_TAG)));
                }
            }
        }
    }
}
