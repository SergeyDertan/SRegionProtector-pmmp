<?php
declare(strict_types=1);

namespace sergeydertan\sregionprotector\region;

use Logger;
use pocketmine\level\Level;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\utils\TextFormat;
use sergeydertan\sregionprotector\main\SaveType;
use sergeydertan\sregionprotector\messenger\Messenger;
use sergeydertan\sregionprotector\provider\DataProvider;
use sergeydertan\sregionprotector\region\chunk\ChunkManager;
use sergeydertan\sregionprotector\region\flags\flag\RegionFlag;
use sergeydertan\sregionprotector\region\flags\flag\RegionSellFlag;
use sergeydertan\sregionprotector\region\flags\flag\RegionTeleportFlag;
use sergeydertan\sregionprotector\region\flags\RegionFlags;

final class RegionManager
{
    /**
     * @var DataProvider
     */
    private $provider;
    /**
     * name lower case -> region
     * @var Region[]
     */
    private $regions = [];
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var ChunkManager
     */
    private $chunkManager;

    /**
     * player -> Region[]
     * @var Region[][]
     */
    private $owners;
    /**
     * player -> Region[]
     * @var Region[][]
     */
    private $members;
    /**
     * @var Messenger
     */
    private $messenger;

    public function __construct($provider, Logger $logger, ChunkManager $chunkManager) //todo provider
    {
        $this->provider = $provider;
        $this->logger = $logger;
        $this->chunkManager = $chunkManager;
        $this->messenger = Messenger::getInstance();
    }

    public function save(int $type, string $initiator = null): void
    {
        $amount = 0;
        foreach ($this->regions as $region) {
            if (!$region->needUpdate) continue;
            $this->provider->saveRegion($region);
            $region->needUpdate = false;
            ++$amount;
        }
        switch ($type) {
            default:
            case SaveType::AUTO:
                $this->logger->info(TextFormat::GREEN . $this->messenger->getMessage("regions-auto-save", ["@amount"], [(string)$amount]));
                break;
            case SaveType::MANUAL:
                $this->logger->info(TextFormat::GREEN . $this->messenger->getMessage("disabling.regions-saved", ["@amount", "@initiator"], [(string)$amount, $initiator]));
                break;
            case SaveType::DISABLING:
                $this->logger->info(TextFormat::GREEN . $this->messenger->getMessage("regions-manual-save", ["@amount"], [(string)$amount]));
                break;
        }
    }

    public function createRegion(string $name, string $creator, Vector3 $pos1, Vector3 $pos2, Level $level): Region
    {
        $minX = min($pos1->x, $pos2->x);
        $minY = min($pos1->y, $pos2->y);
        $minZ = min($pos1->z, $pos2->z);

        $maxX = max($pos1->x, $pos2->x);
        $maxY = max($pos1->y, $pos2->y);
        $maxZ = max($pos1->z, $pos2->z);

        $region = new Region($name, $creator, $level->getName(), $minX, $minY, $minZ, $maxX, $maxY, $maxZ);

        $creator = strtolower($creator);
        if (!isset($this->owners[$creator])) $this->owners[$creator] = [];
        $this->owners[$creator][] = $region;

        foreach ($this->chunkManager->getRegionChunks(
            new Vector3($minX, $minY, $minZ),
            new Vector3($maxX, $maxY, $maxZ),
            $level->getName(), true
        ) as $chunk) {
            $chunk->addRegion($region);
            $region->addChunk($chunk);
        }
        $this->regions[strtolower($region->getName())] = $region;

        //new BlockEntityHealer($level) TODO
        $region->needUpdate = true;
        return $region;
    }

    public function regionExists(string $region): bool
    {
        return isset($this->regions[strtolower($region)]);
    }

    public function init(bool $saveNewFlags): void
    {
        foreach ($this->provider->loadRegionList() as $region) {
            $name = (string)$region["name"];

            $minX = (int)$region["min-x"];
            $minY = (int)$region["min-y"];
            $minZ = (int)$region["min-z"];

            $maxX = (int)$region["max-x"];
            $maxY = (int)$region["max-y"];
            $maxZ = (int)$region["max-z"];

            $owners = (array)$region["owners"];
            $members = (array)$region["members"];

            $creator = (string)$region["creator"];

            $level = (string)$region["level"];
            $priority = (int)$region["priority"];

            $flags = [];

            foreach ($region["flags"] as $name => $flagData) {
                $id = RegionFlags::getFlagId($name);
                $state = (bool)$flagData["state"];
                if ($id === RegionFlags::FLAG_SELL) {
                    $flag = new RegionSellFlag($state, (int)$flagData["price"]);
                } else if ($id === RegionFlags::FLAG_TELEPORT) {
                    $tpLevel = $flagData["level"];
                    $x = isset($flagData["x"]) ? (int)$flagData["x"] : 0;
                    $y = isset($flagData["y"]) ? (int)$flagData["y"] : 0;
                    $z = isset($flagData["z"]) ? (int)$flagData["z"] : 0;
                    $flag = new RegionTeleportFlag($state, new Vector3($x, $y, $z), $tpLevel);
                } else {
                    $flag = new RegionFlag($state);
                }
                $flags[$id] = $flag;
            }

            $c = false;
            if (count($flags) !== RegionFlags::FLAG_AMOUNT) {
                RegionFlags::fixMissingFlags($flags);
                $c = $saveNewFlags;
            }

            $region = new Region($name, $creator, $level, $minX, $minY, $minZ, $maxX, $maxY, $maxZ, $owners, $members, $flags, $priority);
            if ($c) {
                $region->needUpdate = true;
            }
            $this->regions[strtolower($name)] = $region;

            $owners[] = $creator;

            foreach ($owners as $user) {
                if (!isset($this->owners[strtolower($user)])) {
                    $this->owners[strtolower($user)] = [];
                }
                $this->owners[strtolower($user)][] = $region;
                unset($user);
            }

            foreach ($members as $user) {
                if (!isset($this->members[strtolower($user)])) {
                    $this->members[strtolower($user)] = [];
                }
                $this->members[strtolower($user)][] = $region;
                unset($user);
            }

            foreach ($this->chunkManager->getRegionChunks(
                new Vector3($minX, $minY, $minZ),
                new Vector3($maxX, $maxY, $maxZ),
                $level, true
            ) as $chunk) {
                $chunk->addRegion($region);
                $region->addChunk($chunk);
            }
        }
        $this->logger->info(TextFormat::GREEN . $this->messenger->getMessage("loading.regions.success", ["@count"], [(string)count($this->regions)]));
        $this->logger->info(TextFormat::GREEN . $this->messenger->getMessage("loading.chunks.success", ["@count"], [(string)$this->chunkManager->getChunkAmount()]));
    }

    public function getRegion(string $name): ?Region
    {
        return
            isset($this->regions[strtolower($name)]) ?
                $this->regions[strtolower($name)] : null;
    }

    public function changeRegionOwner(Region $region, string $newOwner): void
    {
        $this->clearUsers($region);

        $region->clearUsers();

        $region->setCreator($newOwner);
        $region->setSellFlag(-1, false);

        $newOwner = strtolower($newOwner);
        if (!isset($this->owners[$newOwner])) $this->owners[$newOwner] = [];
        $this->owners[$newOwner][] = $region;
    }

    private function clearUsers(Region $region): void
    {
        $region->addOwner($region->getCreator());
        foreach ($region->getOwners() as $user) {
            $user = strtolower($user);
            foreach ($this->owners[$user] as $id => $rr) {
                if ($rr === $region) unset($this->owners[$user][$id]);
            }
            if (count($this->owners[$user]) === 0) {
                unset($this->owners[$user]);
            }
        }

        foreach ($region->getMembers() as $user) {
            $user = strtolower($user);
            foreach ($this->members[$user] as $id => $rr) {
                if ($rr === $region) unset($this->members[$user][$id]);
            }
            if (count($this->members[$user]) === 0) {
                unset($this->members[$user]);
            }
        }
    }

    public function removeRegion(Region $region): void
    {
        $this->clearUsers($region);

        foreach ($region->getChunks() as $chunk) {
            $chunk->removeRegion($region);
        }

        unset($this->regions[strtolower($region->getName())]);

        $this->provider->removeRegion($region->getName());

        if ($region->getHealerBlockEntity() !== null) {
            $region->getHealerBlockEntity()->close();
        }
    }

    public function checkOverlap(Vector3 $pos1, Vector3 $pos2, string $level, string $creator, bool $checkSellFlag, ?Region $self = null): bool
    {
        $bb = new AxisAlignedBB(
            min($pos1->x, $pos2->x),
            min($pos1->y, $pos2->y),
            min($pos1->z, $pos2->z),

            max($pos1->x, $pos2->x),
            max($pos1->y, $pos2->y),
            max($pos1->z, $pos2->z)
        );

        foreach ($this->chunkManager->getRegionChunks($pos1, $pos2, $level, false) as $chunk) {
            foreach ($chunk->getRegions() as $region) {
                if ($region === $self || !$region->intersectsWith($bb)) continue;
                if ($checkSellFlag && $region->getFlagState(RegionFlags::FLAG_SELL)) return true;
                if ($region->isCreator($creator)) continue;
                return true;
            }
        }
        return false;
    }

    public function getRegionAmount(string $player, int $type = RegionGroup::CREATOR): int
    {
        switch ($type) {
            case RegionGroup::CREATOR:
                if (!isset($this->owners[strtolower($player)])) return 0;
                $amount = 0;
                foreach ($this->owners[strtolower($player)] as $region) {
                    if ($region->isCreator($player)) ++$amount;
                }
                return $amount;
                break;
            case RegionGroup::OWNER:
                if (!isset($this->owners[strtolower($player)])) return 0;
                return count($this->owners[strtolower($player)]);
                break;
            case RegionGroup::MEMBER:
                if (!isset($this->members[strtolower($player)])) return 0;
                return count($this->members[strtolower($player)]);
                break;
        }
        return 0;
    }
}
