<?php
declare(strict_types=1);

namespace sergeydertan\sregionprotector\ui\chest\page;

use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Player;
use sergeydertan\sregionprotector\messenger\Messenger;
use sergeydertan\sregionprotector\region\Region;
use sergeydertan\sregionprotector\util\Tags;

abstract class Page
{
    /**
     * page name -> Page
     * @var Page[]
     */
    public static $pages = [];
    /**
     * @var Item[]
     */
    public static $navigatorsCache = [];
    /**
     * @var Page
     */
    public static $mainPage, $flagsPage, $membersPage, $ownersPage, $removeRegionPage;

    public static function getPage(string $name): ?Page
    {
        return isset(self::$pages[$name]) ? self::$pages[$name] : null;
    }

    public static function init(): void
    {
        self::$mainPage = new MainPage();
        self::$flagsPage = new FlagsPage();
        self::$membersPage = new MembersPage();
        self::$ownersPage = new OwnersPage();
        self::$removeRegionPage = new RemoveRegionPage();

        FlagsPage::initIcons();

        self::$pages = [];

        self::$pages[self::$mainPage->getName()] = self::$mainPage;
        self::$pages[self::$flagsPage->getName()] = self::$flagsPage;
        self::$pages[self::$membersPage->getName()] = self::$membersPage;
        self::$pages[self::$ownersPage->getName()] = self::$ownersPage;
        self::$pages[self::$removeRegionPage->getName()] = self::$removeRegionPage;

        $nbt = new CompoundTag();
        $nbt->setByte(Tags::PREVIOUS_PAGE_TAG, 1);
        self::$navigatorsCache[21] = Item::get(ItemIds::APPLE)->setNamedTag($nbt)->setCustomName(Messenger::getInstance()->getMessage("gui.navigator.previous-page"));

        $nbt = new CompoundTag();
        $nbt->setByte(Tags::REFRESH_PAGE_TAG, 1);
        self::$navigatorsCache[22] = Item::get(ItemIds::COOKIE)->setNamedTag($nbt)->setCustomName(Messenger::getInstance()->getMessage("gui.navigator.refresh"));

        $nbt = new CompoundTag();
        $nbt->setByte(Tags::NEXT_PAGE_TAG, 1);
        self::$navigatorsCache[23] = Item::get(ItemIds::APPLE)->setNamedTag($nbt)->setCustomName(Messenger::getInstance()->getMessage("gui.navigator.next-page"));

        $nbt = new CompoundTag();
        $nbt->setString(Tags::OPEN_PAGE_TAG, self::$mainPage->getName());
        self::$navigatorsCache[26] = Item::get(ItemIds::SLIME_BALL)->setNamedTag($nbt)->setCustomName(Messenger::getInstance()->getMessage("gui.navigator.back"));
    }

    /**
     * @param Region $region
     * @param int $page
     * @return Item[] inventory items, see default pages for examples
     */
    public abstract function getItems(Region $region, int $page = 0): array;

    /**
     * @return Item icon which will be displayed on the main page
     */
    public abstract function getIcon(): Item;

    /**
     * check if player has permission to do action (NOT to open page)
     * @param Player $player
     * @param Region $region
     * @return bool
     */
    public abstract function hasPermission(Player $player, Region $region): bool;

    /**
     * @param int $page
     * @param Item[] $items
     */
    public function prepareItems(array &$items, int $page = 0): void
    {
        foreach ($items as $item) {
            $this->prepareItem($item, $page);
        }
    }

    public function prepareItem(Item $item, int $page): void
    {
        $nbt = $item->hasCompoundTag() ? $item->getNamedTag() : new CompoundTag();
        $nbt->setByte(Tags::IS_UI_ITEM_TAG, 1);
        $nbt->setInt(Tags::CURRENT_PAGE_NUMBER_TAG, $page);
        if ($this->getName() !== null) $nbt->setString(Tags::CURRENT_PAGE_NAME_TAG, $this->getName());
        $item->setNamedTag($nbt);
    }

    public abstract function getName(): ?string;

    /**
     * @param Item $item
     * @param Region $region
     * @param Player $player
     * @return bool, true if page update required
     */
    public function handle(Item $item, Region $region, Player $player): bool
    {
        return false;
    }
}
