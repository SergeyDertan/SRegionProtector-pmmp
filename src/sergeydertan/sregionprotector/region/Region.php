<?php
declare(strict_types=1);

namespace sergeydertan\sregionprotector\region;

use pocketmine\level\Position;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\Server;
use sergeydertan\sregionprotector\blockentity\BlockEntityHealer;
use sergeydertan\sregionprotector\region\chunk\Chunk;
use sergeydertan\sregionprotector\region\flags\flag\RegionFlag;
use sergeydertan\sregionprotector\region\flags\flag\RegionSellFlag;
use sergeydertan\sregionprotector\region\flags\flag\RegionTeleportFlag;
use sergeydertan\sregionprotector\region\flags\RegionFlags;

final class Region
{
    /**
     * @var bool
     */
    public $needUpdate = false;
    /**
     * @var float
     */
    private $minX;
    /**
     * @var float
     */
    private $minY;
    /**
     * @var float
     */
    private $minZ;
    /**
     * @var float
     */
    private $maxX;
    /**
     * @var float
     */
    private $maxY;
    /**
     * @var float
     */
    private $maxZ;
    /**
     * @var string
     */
    private $name;
    /**
     * @var string
     */
    private $level;
    /**
     * @var string
     */
    private $creator;
    /**
     * owners, in lower case
     * @var string[]
     */
    private $owners = [];
    /**
     * members, in lower case
     * @var string[]
     */
    private $members = [];
    /**
     * @var RegionFlag[]
     */
    private $flags = [];
    /**
     * @var int
     */
    private $priority = 0;
    /**
     * for updating priorities
     * @var Chunk[]
     */
    private $chunks = [];
    /**
     * @var int
     */
    private $size;

    public function __construct(string $name, string $creator, string $level, int $minX, int $minY, int $minZ, int $maxX, int $maxY, int $maxZ, array $owners = [], array $members = [], ?array $flags = null, int $priority = 0)
    {
        $this->minX = (float)$minX;
        $this->minY = (float)$minY;
        $this->minZ = (float)$minZ;

        $this->maxX = (float)$maxX;
        $this->maxY = (float)$maxY;
        $this->maxZ = (float)$maxZ;

        $this->name = $name;
        $this->creator = $creator;
        $this->level = $level;

        $this->owners = $owners;
        $this->members = $members;

        if ($flags === null) {
            $flags = RegionFlags::getDefaultFlagList();
        }
        $this->flags = $flags;

        $this->priority = $priority;

        $this->size = (int)(($this->maxX - $this->minX) * ($this->maxY - $this->minY) * ($this->maxZ - $this->minZ));
    }

    public function addChunk(Chunk $chunk): void
    {
        $this->chunks[] = $chunk;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function setPriority(int $priority): void
    {
        $this->priority = $priority;
        foreach ($this->chunks as $chunk) {
            $chunk->updatePriorities();
        }
        $this->needUpdate = true;
    }

    public function clearUsers(): void
    {
        $this->creator = "";
        $this->owners = [];
        $this->members = [];
        $this->needUpdate = true;
    }

    public function isSelling(): bool
    {
        return $this->flags[RegionFlags::FLAG_SELL]->state;
    }

    public function getLevel(): string
    {
        return $this->level;
    }

    public function getCreator(): string
    {
        return $this->creator;
    }

    public function setCreator(string $creator): void
    {
        $this->creator = $creator;
        $this->needUpdate = true;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getFlagState(int $flag): bool
    {
        return $this->flags[$flag]->state;
    }

    public function setFlagState(int $flag, bool $state): void
    {
        $this->flags[$flag]->state = $state;

        $this->needUpdate = true;
    }

    public function setTeleportFlag(?Position $pos, bool $state): void
    {
        /**
         * @var RegionTeleportFlag $flag
         */
        $flag = $this->flags[RegionFlags::FLAG_TELEPORT];

        $flag->setPosition($pos);
        $flag->setState($state);

        $this->needUpdate = true;
    }

    public function setSellFlag(int $price, bool $state): void
    {
        /**
         * @var RegionSellFlag $flag
         */
        $flag = $this->flags[RegionFlags::FLAG_SELL];

        $flag->setPrice($price);
        $flag->setState($state);

        $this->needUpdate = true;
    }

    public function getTeleportFlagPos(): ?Position
    {
        /**
         * @var RegionTeleportFlag $flag
         */
        $flag = $this->flags[RegionFlags::FLAG_TELEPORT];
        return $flag->getPosition();
    }

    public function getSellFlagPrice(): int
    {
        /**
         * @var RegionSellFlag $flag
         */
        $flag = $this->flags[RegionFlags::FLAG_SELL];
        return $flag->price;
    }

    /**
     * @return string[]
     */
    public function getMembers(): array
    {
        return $this->members;
    }

    /**
     * @return string[]
     */
    public function getOwners(): array
    {
        return $this->owners;
    }

    public function isOwner(string $player, bool $creator = false): bool
    {
        return in_array(strtolower($player), $this->owners) || ($creator && strcasecmp($player, $this->creator) === 0);
    }

    public function isMember(string $player): bool
    {
        return in_array(strtolower($player), $this->members);
    }

    public function isCreator(string $player): bool
    {
        return strcasecmp($player, $this->creator) === 0;
    }

    public function removeOwner(string $player): void
    {
        unset($this->owners[array_search(strtolower($player), $this->owners)]);
        $this->needUpdate = true;
    }

    public function removeMember(string $player): void
    {
        unset($this->members[array_search(strtolower($player), $this->members)]);
        $this->needUpdate = true;
    }

    public function addMember(string $player): void
    {
        $player = strtolower($player);
        if (in_array($player, $this->members)) return;
        $this->members[] = $player;
        $this->needUpdate = true;
    }

    public function addOwner(string $player): void
    {
        $player = strtolower($player);
        if (in_array($player, $this->owners)) return;
        $this->owners[] = $player;
        $this->needUpdate = true;
    }

    public function isLivesIn(string $player): bool
    {
        $player = strtolower($player);
        return strcasecmp($player, $this->creator) === 0 || in_array($player, $this->owners) || in_array($player, $this->members);
    }

    public function isNeedUpdate(): bool
    {
        return $this->needUpdate;
    }

    public function setNeedUpdate(bool $needUpdate): void
    {
        $this->needUpdate = $needUpdate;
    }

    public function getMaxX(): float
    {
        return $this->maxX;
    }

    public function getMaxY(): float
    {
        return $this->maxY;
    }

    public function getMaxZ(): float
    {
        return $this->maxZ;
    }

    public function getMinX(): float
    {
        return $this->minX;
    }

    public function getMinY(): float
    {
        return $this->minY;
    }

    public function getMinZ(): float
    {
        return $this->minZ;
    }

    /**
     * @return RegionFlag[]
     */
    public function getFlags(): array
    {
        return $this->flags;
    }

    public function getBoundingBox(): AxisAlignedBB
    {
        return new AxisAlignedBB($this->minX, $this->minY, $this->minZ, $this->maxX, $this->maxY, $this->maxZ);
    }

    /**
     * @return Chunk[]
     */
    public function getChunks(): array
    {
        return $this->chunks;
    }

    public function intersectsWith(AxisAlignedBB $bb, float $epsilon = 0.00001): bool
    {
        if ($bb->maxX - $this->minX - 1 > $epsilon && $this->maxX + 1 - $bb->minX > $epsilon) {
            if ($bb->maxY - $this->minY - 1 > $epsilon && $this->maxY + 1 - $bb->minY > $epsilon) {
                return $bb->maxZ - $this->minZ - 1 > $epsilon && $this->maxZ + 1 - $bb->minZ > $epsilon;
            }
        }
        return false;
    }

    public function isVectorInside(Vector3 $vector): bool
    {
        if ($vector->x <= $this->minX - 1 || $vector->x >= $this->maxX + 1) {
            return false;
        }
        if ($vector->y <= $this->minY - 1 || $vector->y >= $this->maxY + 1) {
            return false;
        }
        return $vector->z > $this->minZ - 1 && $vector->z < $this->maxZ + 1;
    }

    public function getHealerBlockEntity(): ?BlockEntityHealer
    {
        $pos = $this->getHealerPosition();
        if ($pos->level === null) return null;
        $ent = $pos->level->getTile($pos);
        if ($ent instanceof BlockEntityHealer) return $ent;
        return null;
    }

    public function getHealerPosition(): Position
    {
        return Position::fromObject($this->getHealerVector(), Server::getInstance()->getLevelByName($this->level));
    }

    public function getHealerVector(): Vector3
    {
        $x = $this->minX + ($this->maxX - $this->minX) / 2;
        $y = $this->minY + ($this->maxY - $this->minY) / 2;
        $z = $this->minZ + ($this->maxZ - $this->minZ) / 2;
        return new Vector3($x, $y, $z);
    }

    public function getMin(): Vector3
    {
        return new Vector3($this->minX, $this->minY, $this->minZ);
    }

    public function getMax(): Vector3
    {
        return new Vector3($this->maxX, $this->maxY, $this->maxZ);
    }

    public function getSize(): int
    {
        return $this->size;
    }
}
