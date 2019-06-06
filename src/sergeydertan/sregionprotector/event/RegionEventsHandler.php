<?php
declare(strict_types=1);

namespace sergeydertan\sregionprotector\event;

use pocketmine\block\Button;
use pocketmine\block\Chest;
use pocketmine\block\Door;
use pocketmine\block\Farmland;
use pocketmine\block\Furnace;
use pocketmine\block\IronTrapdoor;
use pocketmine\block\Trapdoor;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Event;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\ItemIds;
use pocketmine\level\particle\AngryVillagerParticle;
use pocketmine\level\Position;
use pocketmine\level\sound\DoorSound;
use pocketmine\Player;
use sergeydertan\sregionprotector\messenger\Messenger;
use sergeydertan\sregionprotector\region\chunk\ChunkManager;
use sergeydertan\sregionprotector\region\flags\RegionFlags;
use sergeydertan\sregionprotector\util\Tags;
use sergeydertan\sregionprotector\util\Utils;

final class RegionEventsHandler implements Listener
{
    /**
     * @var ChunkManager
     */
    private $chunkManager;
    /**
     * check if flag enabled
     * @var bool[]
     */
    private $flagStatus;
    /**
     * check if flag requires a message
     * @var bool[]
     */
    private $needMessage;

    /**
     * @var bool
     */
    private $pprioritySystem;

    /**
     * @var bool
     */
    private $showParticle;

    /**
     * @var int
     */
    private $protectedMessageType;

    public function __construct(ChunkManager $chunkManager, array $flagStatus, array $needMessage, bool $prioritySystem, int $protectedMessageType, bool $showParticle)
    {
        $this->chunkManager = $chunkManager;
        $this->flagStatus = $flagStatus;
        $this->needMessage = $needMessage;
        $this->pprioritySystem = $prioritySystem;
        $this->protectedMessageType = $protectedMessageType;
        $this->showParticle = $showParticle;
    }

    /**
     * break & minefarm flags
     * @param BlockBreakEvent $e
     *
     * @priority HIGH
     * @ignoreCancelled true
     */
    public function blockBreak(BlockBreakEvent $e): void
    {
        if (Utils::endsWith($e->getBlock()->getName(), Tags::BLOCK_ORE)) {
            $this->handleEvent(RegionFlags::FLAG_MINEFARM, $e->getBlock(), $e, $e->getPlayer(), false, false);
            if ($e->isCancelled()) {
                $e->setCancelled(false);
                return;
            }
        }
        $this->handleEvent(RegionFlags::FLAG_BREAK, $e->getBlock(), $e, $e->getPlayer());
    }

    private function handleEvent(int $flag, Position $pos, Event $event, Player $player = null, bool $mustBeMember = true, bool $checkPerm = true): void
    {
        if (!$this->flagStatus[$flag]) return;
        if ($checkPerm && ($player !== null && $player->hasPermission("sregionprotector.admin"))) return;
        $chunk = $this->chunkManager->getChunk((int)$pos->x, (int)$pos->z, $pos->level->getName(), true, false);
        if ($chunk === null) return;

        foreach ($chunk->getRegions() as $region) {
            if (!$region->isVectorInside($pos) || ($player !== null && $mustBeMember && $region->isLivesIn($player->getName()))) {
                continue;
            }
            if (!$region->getFlagState($flag)) {
                if ($this->pprioritySystem) {
                    break;
                } else {
                    continue;
                }
            }
            if ($this->showParticle && $player !== null) {
                $pos = $pos->asVector3();
                if ($pos->x % 1 + $pos->y % 1 + $pos->z % 1 === 0) {
                    $pos->add(0.5, 1.3, 0.5);
                }
                $particle = new AngryVillagerParticle($pos);
                foreach ($particle->encode() as $pk) {
                    $player->dataPacket($pk);
                }
            }
            $event->setCancelled();
            if ($player !== null && $this->needMessage[$flag]) {
                Messenger::getInstance()->sendMessage($player, "region.protected." . RegionFlags::getFlagName($flag), [], [], $this->protectedMessageType);
            }
            break;
        }
    }

    /**
     * place flag
     * @param BlockPlaceEvent $e
     *
     * @priority HIGH
     * @ignoreCancelled true
     */
    public function blockPlace(BlockPlaceEvent $e): void
    {
        $this->handleEvent(RegionFlags::FLAG_PLACE, $e->getBlock(), $e, $e->getPlayer());
    }

    /**
     * interact, user, crops destroy, chest access & smart doors flags
     * @param PlayerInteractEvent $e
     *
     * @priority HIGH
     * @ignoreCancelled true
     */
    public function playerInteract(PlayerInteractEvent $e): void
    {
        $block = $e->getBlock();
        $this->handleEvent(RegionFlags::FLAG_INTERACT, $block, $e, $e->getPlayer());
        if ($e->isCancelled()) return;
        if ($block instanceof Door || $block instanceof IronTrapdoor) {
            if ($this->canInteractWith(RegionFlags::FLAG_SMART_DOORS, $block, $e->getPlayer())) {
                if ($block instanceof IronTrapdoor) {
                    $block->setDamage($block->getDamage() ^ 0x08);
                    $block->level->setBlock($block, $block, true);
                    $block->level->addSound(new DoorSound($block));
                    return;
                }
                /**
                 * @var Door $block
                 */

                $damage = $block->getDamage();
                $isUp = ($damage & 8) > 0;
                if ($isUp) {
                    $up = $damage;
                } else {
                    $up = $block->level->getBlock($block->up())->getDamage();
                }
                $isRight = ($up & 1) > 0;

                if ($isUp) {
                    //$second=$block->level->getBlock($block->down())->getSide()
                }

                //TODO smart doors

                $e->setCancelled();
                return;
            }
        }

        if ($e->getItem() !== null && $e->getItem()->getId() === ItemIds::FLINT_AND_STEEL) {
            $this->handleEvent(RegionFlags::FLAG_LIGHTER, $block, $e, $e->getPlayer(), false, false);
            return;
        }
        if ($block instanceof Chest) {
            $this->handleEvent(RegionFlags::FLAG_CHEST_ACCESS, $block, $e, $e->getPlayer(), false, false);
            return;
        }
        if ($block instanceof Farmland) {
            $this->handleEvent(RegionFlags::FLAG_CROPS_DESTROY, $block, $e, $e->getPlayer(), false, false);
            return;
        }
        if ($block instanceof Door || $block instanceof Trapdoor || $block instanceof Button || $block instanceof Furnace) {
            //TODO beacon, hopper, dispenser
            $this->handleEvent(RegionFlags::FLAG_USE, $block, $e, $e->getPlayer(), false, false);
            return;
        }
    }

    private function canInteractWith(int $flag, Position $pos, Player $player): bool
    {
        if (!$this->flagStatus[$flag]) return false;
        $chunk = $this->chunkManager->getChunk($pos->x, $pos->z, $pos->level->getName(), true, false);
        if ($chunk === null) return false;
        foreach ($chunk->getRegions() as $region) {
            if (!$region->isVectorInside($pos)) continue;
            if (!$region->getFlagState($flag)) {
                if ($this->pprioritySystem) {
                    return false;
                } else {
                    continue;
                }
            }
            return $region->isLivesIn($player->getName()) || $player->hasPermission("sregionprotector.admin");
        }
        return false;
    }

    /**
     * pvp, mob damage, lightning strike & invincible flags
     * @param EntityDamageEvent $e
     *
     * @priority HIGH
     * @ignoreCancelled true
     */
    public function entityDamage(EntityDamageEvent $e): void
    {
        $ent = $e->getEntity();
        if (!$ent instanceof Player) return;
        if ($e->getCause() === EntityDamageEvent::CAUSE_FALL) {
            $this->handleEvent(RegionFlags::FLAG_FALL_DAMAGE, $ent, $e, $ent, false, false);
            if ($e->isCancelled()) return;
        }
        if (!$e instanceof EntityDamageByEntityEvent) {
            $this->handleEvent(RegionFlags::FLAG_INVINCIBLE, $ent, $e, $ent, false, false);
            return;
        }
        if ($e->getDamager() instanceof Player) {
            /**
             * @var Player $damager
             */
            $damager = $e->getDamager();
            $this->handleEvent(RegionFlags::FLAG_PVP, $ent, $e, $damager, false, false);
        } else if (false) {
            //TODO mob damager
        } else if (false) {
            //TODO lighting strike
        }
    }
}
