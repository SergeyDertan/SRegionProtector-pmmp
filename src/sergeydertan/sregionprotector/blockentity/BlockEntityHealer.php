<?php
declare(strict_types=1);

namespace sergeydertan\sregionprotector\blockentity;

use pocketmine\level\Level;
use pocketmine\math\AxisAlignedBB;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Player;
use pocketmine\tile\Spawnable;
use sergeydertan\sregionprotector\main\SRegionProtectorMain;
use sergeydertan\sregionprotector\region\flags\RegionFlags;
use sergeydertan\sregionprotector\region\RegionManager;

final class BlockEntityHealer extends Spawnable
{
    const BLOCK_ENTITY_HEALER = "RegionHealer";

    static $HEAL_DELAY;
    static $HEAL_AMOUNT;
    static $FLAG_ENABLED;

    /**
     * @var RegionManager
     */
    private $regionManager;
    /**
     * @var AxisAlignedBB
     */
    private $bb;
    /**
     * @var string
     */
    private $region;
    /**
     * @var int
     */
    private $delay;

    public function __construct(Level $level, CompoundTag $nbt)
    {
        parent::__construct($level, $nbt);
        $this->region = $nbt->getString("region");
        $this->regionManager = SRegionProtectorMain::getInstance()->getRegionManager();

        $this->bb = $this->regionManager->getRegion($this->region)->getBoundingBox();

        $this->delay = self::$HEAL_DELAY;
    }

    public function onUpdate(): bool
    {
        if ($this->closed) return false;
        if (!self::$FLAG_ENABLED) return true;
        $region = $this->regionManager->getRegion($this->region);
        if ($region === null) return false;
        if (!$region->getFlagState(RegionFlags::FLAG_HEAL)) return true;
        if (--$this->delay > 0) return true;
        foreach ($this->level->getNearbyEntities($this->bb) as $entity) {
            if (!$entity instanceof Player) continue;
            $entity->setHealth($entity->getHealth() + self::$HEAL_AMOUNT); //TODO heal
        }
        $this->delay = self::$HEAL_DELAY;
        return true;
    }

    public function spawnTo(Player $player): bool
    {
        return true;
    }

    public function isValid(): bool
    {
        return $this->regionManager->regionExists($this->region);
    }

    protected function writeSaveData(CompoundTag $nbt): void
    {
        $nbt->setString("id", self::BLOCK_ENTITY_HEALER);
        $nbt->setString("region", $this->region);
        $nbt->setInt("x", $this->x);
        $nbt->setInt("y", $this->y);
        $nbt->setInt("z", $this->z);
    }

    protected function addAdditionalSpawnData(CompoundTag $nbt): void
    {
    }

    protected function readSaveData(CompoundTag $nbt): void
    {
        $this->region = $nbt->getString("region");
    }
}
