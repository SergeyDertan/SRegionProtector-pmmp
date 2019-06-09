<?php
declare(strict_types=1);

namespace sergeydertan\sregionprotector\region\selector;

use pocketmine\block\Block;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;
use pocketmine\Player;
use sergeydertan\sregionprotector\util\Utils;

final class RegionSelector
{
    /**
     * @var int
     */
    private $sessionLifeTime;
    /**
     * @var SelectorSession[]
     */
    private $sessions = [];
    /**
     * @var int
     */
    private $borderBlock;
    /**
     * player loader id -> Vector3[]
     * @var Vector3[][]
     */
    private $borders;

    public function __construct(int $sessionLifeTime, Block $borderBlock)
    {
        $this->sessionLifeTime = $sessionLifeTime;
        $this->borderBlock = $borderBlock->getRuntimeId();
    }

    public function removeSession(Player $player): void
    {
        unset($this->sessions[$player->getLoaderId()]);
    }

    public function getSession(Player $player): SelectorSession
    {
        if (isset($this->sessions[$player->getLoaderId()])) return $this->sessions[$player->getLoaderId()];
        $session = $this->sessions[$player->getLoaderId()] = new SelectorSession($this->sessionLifeTime);
        return $session;
    }

    public function clear(): void
    {
        $current = Utils::currentTimeMillis();
        foreach ($this->sessions as $id => $session) {
            if ($session->expirationTime < $current) unset($this->sessions[$id]);
        }
    }

    public function sessionExists(Player $player): bool
    {
        return isset($this->sessions[$player->getLoaderId()]);
    }

    public function hasBorders(Player $player): bool
    {
        return isset($this->borders[$player->getLoaderId()]);
    }

    public function calculateEdgesLength(Vector3 $pos1, Vector3 $pos2): int
    {
        $size = 4 * (
                abs(max($pos1->x, $pos2->x) - min($pos1->x, $pos2->x)) +
                abs(max($pos1->y, $pos2->y) - min($pos1->y, $pos2->y)) +
                abs(max($pos1->z, $pos2->z) - min($pos1->z, $pos2->z))
            );
        if ($size < 0) return PHP_INT_MAX;
        return (int)$size - 4;
    }

    public function showBorders(Player $target, Vector3 $pos1, Vector3 $pos2): void
    {
        $minX = (int)min($pos1->x, $pos2->x);
        $minY = (int)min($pos1->y, $pos2->y);
        $minZ = (int)min($pos1->z, $pos2->z);

        $maxX = (int)max($pos1->x, $pos2->x);
        $maxY = (int)max($pos1->y, $pos2->y);
        $maxZ = (int)max($pos1->z, $pos2->z);

        $blocks = [];

        for ($yt = $minY; $yt <= $maxY; ++$yt) {
            for ($xt = $minX; ; $xt = $maxX) {
                for ($zt = $minZ; ; $zt = $maxZ) {
                    $pk = new UpdateBlockPacket();
                    $pk->x = $xt;
                    $pk->y = $yt;
                    $pk->z = $zt;

                    $pk->flags = UpdateBlockPacket::FLAG_ALL;
                    $pk->blockRuntimeId = $this->borderBlock;
                    $blocks[] = new Vector3($xt, $yt, $zt);

                    $target->dataPacket($pk);
                    if ($zt === $maxZ) break;
                }
                if ($xt === $maxX) break;
            }
        }

        for ($yd = $minY; ; $yd = $maxY) {
            for ($zd = $minZ; ; $zd = $maxZ) {
                for ($zx = $minX; $zx <= $maxX; ++$zx) {
                    $pk = new UpdateBlockPacket();
                    $pk->x = $zx;
                    $pk->y = $yd;
                    $pk->z = $zd;
                    $pk->flags = UpdateBlockPacket::FLAG_ALL;
                    $pk->blockRuntimeId = $this->borderBlock;
                    $target->dataPacket($pk);
                    $blocks[] = new Vector3($zx, $yd, $zd);
                }
                if ($zd === $maxZ) break;
            }

            for ($xd = $minX; ; $xd = $maxX) {
                for ($zx = $minZ; $zx <= $maxZ; ++$zx) {
                    $pk = new UpdateBlockPacket();
                    $pk->x = $xd;
                    $pk->y = $yd;
                    $pk->z = $zx;
                    $pk->flags = UpdateBlockPacket::FLAG_ALL;
                    $pk->blockRuntimeId = $this->borderBlock;

                    $target->dataPacket($pk);
                    $blocks[] = new Vector3($xd, $yd, $zx);
                }
                if ($xd === $maxX) break;
            }
            if ($yd === $maxY) break;
        }
        $this->borders[$target->getLoaderId()] = $blocks;
    }

    public function removeBorders(Player $target, bool $send = false): void
    {
        if ($send) $target->level->sendBlocks([$target], $this->borders[$target->getLoaderId()]);
        unset($this->borders[$target->getLoaderId()]);
    }
}
