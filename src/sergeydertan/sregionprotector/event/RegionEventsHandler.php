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
use pocketmine\entity\projectile\SplashPotion;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockBurnEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\LeavesDecayEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityExplodeEvent;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\event\entity\EntitySpawnEvent;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\event\Event;
use pocketmine\event\level\ChunkUnloadEvent;
use pocketmine\event\level\LevelLoadEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerBedEnterEvent;
use pocketmine\event\player\PlayerBucketEmptyEvent;
use pocketmine\event\player\PlayerBucketFillEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerMoveEvent;
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

    /**
     * @param EntitySpawnEvent $e
     *
     * @priority HIGH
     */
    public function entitySpawn(EntitySpawnEvent$e):void{
        $ev=new EmptyEvent();
        //TODO
    }

    //TODO lighting strike event

    //TODO block ignite event

    /**
     * fire flag
     * @param BlockBurnEvent $e
     *
     * @priority HIGH
     * @ignoreCancelled true
     */
    public function blockBurn(BlockBurnEvent $e): void
    {
        $this->handleEvent(RegionFlags::FLAG_FIRE, $e->getBlock(), $e);
    }

    /**
     * leaves decay flag
     * @param LeavesDecayEvent $e
     *
     * @priority HIGH
     * @ignoreCancelled true
     */
    public function leavesDecay(LeavesDecayEvent $e): void
    {
        $this->handleEvent(RegionFlags::FLAG_LEAVES_DECAY, $e->getBlock(), $e);
    }

    /**
     * explode (creeper & tnt) & explode block break event
     * @param EntityExplodeEvent $e
     *
     * @priority HIGH
     * @ignoreCancelled true
     */
    public function entityExplode(EntityExplodeEvent $e): void
    {
        $this->handleEvent(RegionFlags::FLAG_EXPLODE, $e->getPosition(), $e);
        if ($e->isCancelled()) return;
        $blocks = $e->getBlockList();
        foreach ($blocks as $id => $block) {
            $this->handleEvent(RegionFlags::FLAG_EXPLODE_BLOCK_BREAK, $block, $e);
            if ($e->isCancelled()) {
                unset($blocks[$id]);
                $e->setCancelled(false);
            }
        }
        $e->setBlockList($blocks);
    }

    /**
     * potion launch flag
     * @param ProjectileLaunchEvent $e
     *
     * @priority HIGH
     * @ignoreCancelled true
     */
    public function projectileLaunch(ProjectileLaunchEvent $e): void
    {
        if (!$e->getEntity() instanceof SplashPotion) return;
        //TODO shooting entity
    }

    /**
     * send chat & receive chat flags
     * @param PlayerChatEvent $e
     *
     * @priority HIGH
     * @ignoreCancelled true
     */
    public function playerChat(PlayerChatEvent $e): void
    {
        $this->handleEvent(RegionFlags::FLAG_SEND_CHAT, $e->getPlayer(), $e, $e->getPlayer());
        if ($e->isCancelled()) return;

        $recipients = $e->getRecipients();
        foreach ($recipients as $id => $recipient) {
            if (!$recipient instanceof Player) continue;
            $this->handleEvent(RegionFlags::FLAG_RECEIVE_CHAT, $recipient, $e, $recipient);
            if ($e->isCancelled()) {
                unset($recipient[$id]);
                $e->setCancelled(false);
            }
        }
        $e->setRecipients($recipients);
    }

    /**
     * item drop flag
     * @param PlayerDropItemEvent $e
     *
     * @priority HIGH
     * @ignoreCancelled true
     */
    public function playerDropItem(PlayerDropItemEvent $e): void
    {
        $this->handleEvent(RegionFlags::FLAG_ITEM_DROP, $e->getPlayer(), $e, $e->getPlayer());
    }

    /**
     * @param PlayerMoveEvent $e
     *
     * @priority HIGH
     * @ignoreCancelled true
     */
    public function playerMove(PlayerMoveEvent $e): void
    {
        $this->handleEvent(RegionFlags::FLAG_MOVE, $e->getTo(), $e, $e->getPlayer());
    }

    /**
     * health regen flag
     * @param EntityRegainHealthEvent $e
     *
     * @priority HIGH
     * @ignoreCancelled true
     */
    public function entityRegainHealth(EntityRegainHealthEvent $e): void
    {
        if (!$e->getEntity() instanceof Player) return;
        /**
         * @var Player $ent
         */
        $ent = $e->getEntity();
        $this->handleEvent(RegionFlags::FLAG_HEALTH_REGEN, $ent, $e, $ent);
    }

    //TODO redstone update flag

    //TODO liquid flow flag

    /**
     * sleep flag
     * @param PlayerBedEnterEvent $e
     *
     * @priority HIGH
     * @ignoreCancelled true
     */
    public function playerBedEnter(PlayerBedEnterEvent $e): void
    {
        $this->handleEvent(RegionFlags::FLAG_SLEEP, $e->getBed(), $e, $e->getPlayer());
    }

    /**
     * chunk loader flag
     * @param ChunkUnloadEvent $e
     *
     * @priority HIGH
     * @ignoreCancelled true
     */
    public function chunkUnload(ChunkUnloadEvent $e): void
    {
        if (!$this->flagStatus[RegionFlags::FLAG_CHUNK_LOADER]) return;
        $chunk = $this->chunkManager->getChunk($e->getChunk()->getX(), $e->getChunk()->getZ(), $e->getLevel()->getName(), false, false);
        if ($chunk === null) return;
        foreach ($chunk->getRegions() as $region) {
            if (!$region->getFlagState(RegionFlags::FLAG_CHUNK_LOADER)) return;
            $e->setCancelled();
            break;
        }
    }

    /**
     * chunk loader flag
     * @param LevelLoadEvent $e
     *
     * @priority HIGH
     */
    public function levelLoad(LevelLoadEvent $e): void
    {
        if (!$this->flagStatus[RegionFlags::FLAG_CHUNK_LOADER]) return;
        $chunks = $this->chunkManager->getLevelChunks($e->getLevel()->getName());
        foreach ($chunks as $chunk) {
            foreach ($chunk->getRegions() as $region) {
                if (!$region->getFlagState(RegionFlags::FLAG_CHUNK_LOADER)) continue;
                $e->getLevel()->loadChunk((int)$chunk->getX(), (int)$chunk->getZ());
            }
        }
    }

    //TODO frame item drop flag

    //TODO prevent portal from spawning in region

    /**
     * @param PlayerBucketFillEvent $e
     *
     * @priority HIGH
     * @ignoreCancelled true
     */
    public function playerBucketFill(PlayerBucketFillEvent $e): void
    {
        $this->handleEvent(RegionFlags::FLAG_BUCKET_FILL, $e->getBlockClicked(), $e, $e->getPlayer());
    }

    /**
     * @param PlayerBucketEmptyEvent $e
     *
     * @priority HIGH
     * @ignoreCancelled true
     */
    public function playerBucketEmpty(PlayerBucketEmptyEvent $e): void
    {
        $this->handleEvent(RegionFlags::FLAG_BUCKET_EMPTY, $e->getBlockClicked(), $e, $e->getPlayer());
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
}
