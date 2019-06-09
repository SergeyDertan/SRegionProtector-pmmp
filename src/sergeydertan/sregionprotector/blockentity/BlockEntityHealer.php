<?php
declare(strict_types=1);

namespace sergeydertan\sregionprotector\blockentity;

use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\level\Level;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Player;
use pocketmine\tile\Spawnable;
use sergeydertan\sregionprotector\region\flags\RegionFlags;
use sergeydertan\sregionprotector\region\RegionManager;
use sergeydertan\sregionprotector\util\Tags;

final class BlockEntityHealer extends Spawnable
{
    const BLOCK_ENTITY_HEALER = "RegionHealer";
    /**
     * @var int
     */
    static $HEAL_DELAY;
    /**
     * @var float
     */
    static $HEAL_AMOUNT;
    /**
     * @var bool
     */
    static $FLAG_ENABLED;

    /**
     * @var RegionManager
     */
    private static $regionManager;
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

        $this->delay = static::$HEAL_DELAY;
    }

    public static function setRegionManager(RegionManager $regionManager): void
    {
        static::$regionManager = $regionManager;
    }

    public static function getDefaultNBT(Vector3 $pos, string $region): CompoundTag
    {
        $nbt = parent::createNBT($pos);
        $nbt->setString(Tags::REGION_TAG, $region);
        return $nbt;
    }

    public function onUpdate(): bool
    {
        if ($this->closed) return false;
        if (!static::$FLAG_ENABLED) return true;
        $region = static::$regionManager->getRegion($this->region);
        if ($region === null) return false;
        if (!$region->getFlagState(RegionFlags::FLAG_HEAL)) return true;
        if (--$this->delay > 0) return true;
        foreach ($this->level->getNearbyEntities($this->bb) as $entity) {
            if (!$entity instanceof Player) continue;
            $entity->heal(new EntityRegainHealthEvent($entity, static::$HEAL_AMOUNT, EntityRegainHealthEvent::CAUSE_CUSTOM));
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
        return static::$regionManager->regionExists($this->region);
    }

    protected function writeSaveData(CompoundTag $nbt): void
    {
        $nbt->setString(Tags::ID_TAG, self::BLOCK_ENTITY_HEALER);
        $nbt->setString(Tags::REGION_TAG, $this->region);
        $nbt->setInt(Tags::X_TAG, $this->x);
        $nbt->setInt(Tags::Y_TAG, $this->y);
        $nbt->setInt(Tags::Z_TAG, $this->z);
    }

    protected function addAdditionalSpawnData(CompoundTag $nbt): void
    {
    }

    protected function readSaveData(CompoundTag $nbt): void
    {
        $this->region = $nbt->getString(Tags::REGION_TAG);

        $region = static::$regionManager->getRegion($this->region);
        if ($region === null) return;
        $this->bb = $region->getBoundingBox();
    }
}
