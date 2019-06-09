<?php
declare(strict_types=1);

namespace sergeydertan\sregionprotector\region\selector;

use pocketmine\level\Position;
use sergeydertan\sregionprotector\util\Utils;

final class SelectorSession
{
    const ACTION_TIMEOUT = 500;
    /**
     * @var int
     */
    public $lastAction;
    /**
     * @var Position
     */
    public $pos1, $pos2;
    public $nextPos = true;
    /**
     * @var int
     */
    public $expirationTime;
    /**
     * @var int
     */
    private $lifeTime;

    public function __construct(int $lifeTime)
    {
        $this->expirationTime = Utils::currentTimeMillis() + $lifeTime;
        $this->lifeTime = $lifeTime;
        $this->lastAction = Utils::currentTimeMillis() - self::ACTION_TIMEOUT - 1;
    }

    public function getPos1(): Position
    {
        return $this->pos1;
    }

    public function setPos1(Position $pos1): void
    {
        $this->pos1 = $pos1;
    }

    public function getPos2(): Position
    {
        return $this->pos2;
    }

    public function setPos2(Position $pos2): void
    {
        $this->pos2 = $pos2;
    }

    public function getExpirationTime(): int
    {
        return $this->expirationTime;
    }

    public function calculateSize(): int
    {
        $minX = min($this->pos1->x, $this->pos2->x);
        $minY = min($this->pos1->y, $this->pos2->y);
        $minZ = min($this->pos1->z, $this->pos2->z);

        $maxX = max($this->pos1->x, $this->pos2->x);
        $maxY = max($this->pos1->y, $this->pos2->y);
        $maxZ = max($this->pos1->z, $this->pos2->z);

        $size = ($maxX - $minX) * ($maxY - $minY) * ($maxZ - $minZ);

        if ($size < 0) return PHP_INT_MAX;

        return (int)$size;
    }

    public function setNextPos(Position $pos): bool
    {
        if (Utils::currentTimeMillis() - $this->lastAction < self::ACTION_TIMEOUT) return false;
        if ($this->nextPos) {
            $this->pos1 = $pos;
        } else {
            $this->pos2 = $pos;
        }
        $this->nextPos = !$this->nextPos;
        $this->lastAction = Utils::currentTimeMillis();
        $this->expirationTime = Utils::currentTimeMillis() + $this->lifeTime;
        return true;
    }

    public function fixHeight(): void //TODO get dimension
    {
        if ($this->pos1->y > 255) $this->pos1->y = 255;
        if ($this->pos2->y > 255) $this->pos2->y = 255;

        if ($this->pos1->y < 0) $this->pos1->y = 0;
        if ($this->pos2->y < 0) $this->pos2->y = 0;
    }

    public function expandUp(int $y): void
    {
        if ($this->pos1->y > $this->pos2->y) {
            $this->pos1->y += $y;
        } else {
            $this->pos2->y += $y;
        }
    }

    public function expandDown(int $y): void
    {
        if ($this->pos1->y < $this->pos2->y) {
            $this->pos1->y -= $y;
        } else {
            $this->pos2->y -= $y;
        }
    }

    public function expandRadius(int $l): void
    {
        if ($this->pos1->x > $this->pos2->x) {
            $this->pos1->x += $l;
            $this->pos2->x -= $l;
        } else {
            $this->pos1->x -= $l;
            $this->pos2->x += $l;
        }

        if ($this->pos1->y > $this->pos2->y) {
            $this->pos1->y += $l;
            $this->pos2->y -= $l;
        } else {
            $this->pos1->y -= $l;
            $this->pos2->y += $l;
        }

        if ($this->pos1->z > $this->pos2->z) {
            $this->pos1->z += $l;
            $this->pos2->z -= $l;
        } else {
            $this->pos1->z -= $l;
            $this->pos2->z += $l;
        }
    }
}
