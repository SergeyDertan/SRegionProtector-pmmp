<?php
declare(strict_types=1);

namespace sergeydertan\sregionprotector\region\chunk;

use Logger;
use pocketmine\math\Vector3;
use pocketmine\scheduler\Task;
use pocketmine\scheduler\TaskScheduler;
use sergeydertan\sregionprotector\region\Region;

final class ChunkManager
{
    /**
     * level -> Chunk[]
     * @var Chunk[][]
     */
    private $chunks = [];
    /**
     * @var Logger
     */
    private $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    public function init(bool $emptyChunksAutoRemoving, int $removePeriod, TaskScheduler $scheduler)
    {
        if (!$emptyChunksAutoRemoving) return;
        $scheduler->scheduleDelayedRepeatingTask(new class($this) extends Task
        {
            private $chunkManager;

            public function __construct(ChunkManager $chunkManager)
            {
                $this->chunkManager = $chunkManager;
            }

            function onRun(int $tick): void
            {
                $this->chunkManager->removeEmptyChunks();
            }
        }, $removePeriod * 20, $removePeriod * 20);
    }

    public function getRegion(Vector3 $pos, string $level): ?Region
    {
        $chunk = $this->getChunk($pos->x, $pos->z, $level);
        if ($chunk === null) return null;
        foreach ($chunk->getRegions() as $region) {
            if ($region->isVectorInside($pos)) return $region;
        }
        return null;
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

    public function getChunk(int $x, int $z, string $level, bool $shiftRight = true, bool $create = false): ?Chunk
    {
        $chunks = isset($this->chunks[$level]) ? $this->chunks[$level] : null;
        if ($chunks === null && !$create) return null;

        if ($shiftRight) {
            $x >>= 4;
            $z >>= 4;
        }

        if ($chunks === null) {
            $chunks = [];
        }

        $hash = self::chunkHash($x, $z);
        if (!isset($chunks[$hash]) && !$create) return null;
        $chunk = new Chunk($x, $z);
        $chunks[$hash] = $chunk;
        $this->chunks[$level] = $chunks;
        return $chunk;
    }

    public static function chunkHash(int $x, int $z): int
    {
        return (($x & 0xFFFFFFFF) << 32) | ($z & 0xFFFFFFFF);
    }
}
