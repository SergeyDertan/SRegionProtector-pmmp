<?php
declare(strict_types=1);

namespace sergeydertan\sregionprotector\region\flags\flag;

use pocketmine\level\Location;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\Server;

final class RegionTeleportFlag extends RegionFlag
{
    /**
     * @var Vector3|Location
     */
    public $position;

    /**
     * @var string
     */
    public $level;

    public function __construct(bool $state = false, Vector3 $position = null, string $level = null)
    {
        parent::__construct($state);
        $this->position = $position !== null ? $position->asVector3() : null;
        $this->level = $level;
    }

    public function getPosition(): ?Position
    {
        if ($this->level === null) return null;
        $level = Server::getInstance()->getLevelByName($this->level);
        if ($level === null) return null;
        return clone $this->position;
    }

    public function setPosition(?Position $position): void
    {
        if ($position === null) return;
        $this->position = clone $position;
        $this->level = $position->level->getName();
    }
}
