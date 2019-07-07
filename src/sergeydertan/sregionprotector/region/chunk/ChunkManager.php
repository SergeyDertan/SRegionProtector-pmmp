<?php
declare(strict_types=1);

namespace sergeydertan\sregionprotector\region\chunk;

use pocketmine\math\Vector3;
use pocketmine\scheduler\TaskScheduler;
use sergeydertan\sregionprotector\region\Region;
use sergeydertan\sregionprotector\util\Task;

final class ChunkManager
{
    /**
     * level -> [hash -> Chunk]
     * @var Chunk[][]
     */
    private $chunks = [];

    public function init(bool $emptyChunksAutoRemoving, int $removePeriod, TaskScheduler $scheduler)
    {
        if (!$emptyChunksAutoRemoving) return;
        $scheduler->scheduleDelayedRepeatingTask(new Task(function (): void {
            $this->removeEmptyChunks();
        }), $removePeriod * 20, $removePeriod * 20);
    }

    public function removeEmptyChunks(): void
    {
        foreach ($this->chunks as $level => $chunks) {
            foreach ($chunks as $id => $chunk) {
                if (empty($chunk->getRegions())) unset($chunks[$id]);
            }
            if (empty($chunks)) {
                unset($this->chunks[$level]);
            } else {
                $this->chunks[$level] = $chunks;
            }
        }
    }

    public function getRegion(Vector3 $pos, string $level): ?Region
    {
        $chunk = $this->getChunk((int)$pos->x, (int)$pos->z, $level);
        if ($chunk === null) return null;
        foreach ($chunk->getRegions() as $region) {
            if ($region->isVectorInside($pos)) return $region;
        }
        return null;
    }

    public function getChunk(int $x, int $z, string $level, bool $shiftRight = true, bool $create = false): ?Chunk
    {
        $chunks = isset($this->chunks[$level]) ? $this->chunks[$level] : null;
        if ($chunks === null && !$create) {
            return null;
        }

        if ($shiftRight) {
            $x >>= 4;
            $z >>= 4;
        }

        if ($chunks === null) {
            $chunks = [];
        }

        $hash = self::chunkHash($x, $z);
        if (!isset($chunks[$hash]) && !$create) {
            return null;
        }
        if (isset($chunks[$hash])) {
            return $chunks[$hash];
        }
        $chunk = new Chunk($x, $z);
        $chunks[$hash] = $chunk;
        $this->chunks[$level] = $chunks;
        return $chunk;
    }

    public static function chunkHash(int $x, int $z): int
    {
        return (($x & 0xFFFFFFFF) << 32) | ($z & 0xFFFFFFFF);
    }

    public function getChunkAmount(): int
    {
        $amount = 0;
        foreach ($this->chunks as $chunks) $amount += count($chunks);
        return $amount;
    }

    /**
     * @param Vector3 $pos1
     * @param Vector3 $pos2
     * @param string $level
     * @param bool $create
     * @return Chunk[]
     */
    public function getRegionChunks(Vector3 $pos1, Vector3 $pos2, string $level, bool $create = false): array
    {
        /**
         * @var $chunks Chunk[]
         */
        $chunks = [];

        $minX = (int)min($pos1->x, $pos2->x);
        $minZ = (int)min($pos1->z, $pos2->z);

        $maxX = (int)max($pos1->x, $pos2->x);
        $maxZ = (int)max($pos1->z, $pos2->z);

        $x = $minX;

        while ($x <= $maxX) {
            $z = $minZ;
            while ($z <= $maxZ) {
                $chunk = $this->getChunk($x, $z, $level, true, $create);
                if ($chunk !== null) $chunks[] = $chunk;
                if ($z === $maxZ) break;
                $z += 16;
                if ($z > $maxZ) $z = $maxZ;
            }
            if ($x === $maxX) break;
            $x += 16;
            if ($x > $maxX) $x = $maxX;
        }
        return $chunks;
    }

    /**
     * @param string $level
     * @return Chunk[]
     */
    public function getLevelChunks(string $level): array
    {
        return isset($this->chunks[$level]) ? $this->chunks[$level] : [];
    }
}
