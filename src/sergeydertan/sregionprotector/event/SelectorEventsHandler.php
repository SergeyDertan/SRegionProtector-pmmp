<?php
declare(strict_types=1);

namespace sergeydertan\sregionprotector\event;

use pocketmine\block\Air;
use pocketmine\block\Block;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\level\Position;
use pocketmine\Player;
use sergeydertan\sregionprotector\messenger\Messenger;
use sergeydertan\sregionprotector\region\selector\RegionSelector;

final class SelectorEventsHandler implements Listener
{
    /**
     * @var RegionSelector
     */
    private $regionSelector;

    public function __construct(RegionSelector $regionSelector)
    {
        $this->regionSelector = $regionSelector;
    }

    public function playerQuit(PlayerQuitEvent $e): void
    {
        $this->regionSelector->removeSession($e->getPlayer());
        $this->regionSelector->removeBorders($e->getPlayer(), false);
    }

    /**
     * @param PlayerInteractEvent $e
     *
     * @priority HIGHEST
     * @ignoreCancelled true
     */
    public function playerInteract(PlayerInteractEvent $e): void
    {
        if ($this->selectPosition($e->getPlayer(), $e->getBlock(), $e->getItem())) $e->setCancelled();
    }

    private function selectPosition(Player $player, Block $pos, Item $item): bool
    {
        if ($pos instanceof Air || $item->getId() !== ItemIds::WOODEN_AXE) return false;
        if (!$player->hasPermission("sregionprotector.wand")) return false;
        $session = $this->regionSelector->getSession($player);
        if (!$session->setNextPos(Position::fromObject($pos, $pos->level))) return false;
        if ($session->nextPos) {
            Messenger::getInstance()->sendMessage($player, "region.selection.pos2");
        } else {
            Messenger::getInstance()->sendMessage($player, "region.selection.pos1");
        }
        return true;
    }

    /**
     * @param BlockBreakEvent $e
     *
     * @priority HIGHEST
     * @ignoreCancelled true
     */
    public function blockBreak(BlockBreakEvent $e): void
    {
        if ($this->selectPosition($e->getPlayer(), $e->getBlock(), $e->getItem())) $e->setCancelled();
    }
}
