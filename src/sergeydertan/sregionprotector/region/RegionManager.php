<?php
declare(strict_types=1);

namespace sergeydertan\sregionprotector\region;

use pocketmine\level\Level;
use pocketmine\level\Location;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use sergeydertan\sregionprotector\blockentity\BlockEntityHealer;
use sergeydertan\sregionprotector\main\SaveType;
use sergeydertan\sregionprotector\messenger\Messenger;
use sergeydertan\sregionprotector\provider\DataProvider;
use sergeydertan\sregionprotector\region\chunk\ChunkManager;
use sergeydertan\sregionprotector\region\flags\flag\RegionFlag;
use sergeydertan\sregionprotector\region\flags\flag\RegionSellFlag;
use sergeydertan\sregionprotector\region\flags\flag\RegionTeleportFlag;
use sergeydertan\sregionprotector\region\flags\RegionFlags;
use sergeydertan\sregionprotector\util\Tags;

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

    public function __construct(DataProvider $provider, ChunkManager $chunkManager)
    {
        $this->provider = $provider;
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
                //$this->logger->info(TextFormat::GREEN . $this->messenger->getMessage("regions-auto-save", ["@amount"], [(string)$amount]));
                break;
            case SaveType::DISABLING:
                //$this->logger->info(TextFormat::GREEN . $this->messenger->getMessage("disabling.regions-saved", ["@amount", "@initiator"], [(string)$amount, $initiator]));
                break;
            case SaveType::MANUAL:
                //$this->logger->info(TextFormat::GREEN . $this->messenger->getMessage("regions-manual-save", ["@amount"], [(string)$amount]));
                break;
        }
    }

    public function createRegion(string $name, string $creator, Vector3 $pos1, Vector3 $pos2, Level $level): Region
    {
        $minX = (int)min($pos1->x, $pos2->x);
        $minY = (int)min($pos1->y, $pos2->y);
        $minZ = (int)min($pos1->z, $pos2->z);

        $maxX = (int)max($pos1->x, $pos2->x);
        $maxY = (int)max($pos1->y, $pos2->y);
        $maxZ = (int)max($pos1->z, $pos2->z);

        $region = new Region($name, $creator, $level->getName(), $minX, $minY, $minZ, $maxX, $maxY, $maxZ);

        $creator = strtolower($creator);
        if (!isset($this->owners[$creator])) $this->owners[$creator] = [];
        $this->owners[$creator][] = $region;

        foreach ($this->chunkManager->getRegionChunks(
            $region->getMin(),
            $region->getMax(),
            $level->getName(), true
        ) as $chunk) {
            $chunk->addRegion($region);
            $region->addChunk($chunk);
        }
        $this->regions[strtolower($region->getName())] = $region;

        new BlockEntityHealer($level, BlockEntityHealer::getDefaultNBT($region->getHealerVector(), $name));
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
            $name = (string)$region[Tags::NAME_TAG];

            $minX = (int)$region[Tags::MIN_X_TAG];
            $minY = (int)$region[Tags::MIN_Y_TAG];
            $minZ = (int)$region[Tags::MIN_Z_TAG];

            $maxX = (int)$region[Tags::MAX_X_TAG];
            $maxY = (int)$region[Tags::MAX_Y_TAG];
            $maxZ = (int)$region[Tags::MAX_Z_TAG];

            $owners = (array)$region[Tags::OWNERS_TAG];
            $members = (array)$region[Tags::MEMBERS_TAG];

            $creator = (string)$region[Tags::CREATOR_TAG];

            $level = (string)$region[Tags::LEVEL_TAG];
            $priority = (int)$region[Tags::PRIORITY_TAG];

            $flags = [];

            foreach ($region[Tags::FLAGS_TAG] as $flagName => $flagData) {
                $id = RegionFlags::getFlagId($flagName);
                $state = (bool)$flagData[Tags::STATE_TAG];
                if ($id === RegionFlags::FLAG_SELL) {
                    $flag = new RegionSellFlag($state, (int)$flagData[Tags::PRICE_TAG]);
                } else if ($id === RegionFlags::FLAG_TELEPORT) {
                    $tpLevel = $flagData[Tags::LEVEL_TAG];
                    $x = isset($flagData[Tags::X_TAG]) ? (float)$flagData[Tags::X_TAG] : 0;
                    $y = isset($flagData[Tags::Y_TAG]) ? (float)$flagData[Tags::Y_TAG] : 0;
                    $z = isset($flagData[Tags::Z_TAG]) ? (float)$flagData[Tags::Z_TAG] : 0;
                    $yaw = isset($flagData[Tags::YAW_TAG]) ? (float)$flagData[Tags::YAW_TAG] : 0;
                    $pitch = isset($flagData[Tags::PITCH_TAG]) ? (float)$flagData[Tags::PITCH_TAG] : 0;
                    $flag = new RegionTeleportFlag($state, new Location($x, $y, $z, $yaw, $pitch), $tpLevel);
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
        //$this->logger->info(TextFormat::GREEN . $this->messenger->getMessage("loading.success", ["@regions", "@chunks"], [(string)count($this->regions), (string)$this->chunkManager->getChunkAmount()]));
    }

    public function getRegion(string $name): ?Region
    {
        return isset($this->regions[strtolower($name)]) ?
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

    public function getRegionAmount(string $player, int $group = RegionGroup::CREATOR): int
    {
        return count($this->getPlayersRegionList($player, $group));
    }

    /**
     * @param string $player
     * @param int $regionGroup
     * @return Region[]
     * @see RegionGroup
     */
    public function getPlayersRegionList(string $player, int $regionGroup = RegionGroup::CREATOR): array
    {
        $player = strtolower($player);
        switch ($regionGroup) {
            case RegionGroup::CREATOR:
                if (!isset($this->owners[$player])) return [];
                $regions = [];
                foreach ($this->owners[$player] as $region) {
                    if ($region->isCreator($player)) $regions[] = $region;
                }
                return $regions;
                break;
            case RegionGroup::OWNER:
                if (isset($this->owners[$player])) return $this->owners[$player];
                return [];
                break;
            case RegionGroup::MEMBER:
                if (isset($this->members[$player])) return $this->members[$player];
                return [];
            default:
                return [];
                break;
        }
    }

    public function addMember(Region $region, string $target): void
    {
        $target = strtolower($target);
        $region->addMember($target);

        if (!isset($this->members[$target])) $this->members[$target] = [];
        $this->members[$target][] = $region;
    }

    public function addOwner(Region $region, string $target): void
    {
        $target = strtolower($target);
        $region->addOwner($target);

        if (!isset($this->owners[$target])) $this->owners[$target] = [];
        $this->owners[$target][] = $region;
    }

    public function removeMember(Region $region, string $target): void
    {
        $target = strtolower($target);

        $region->removeMember($target);
        unset($this->members[$target][array_search($region, $this->members[$target])]);
        if (empty($this->members[$target])) unset($this->members[$target]);
    }

    public function removeOwner(Region $region, string $target): void
    {
        $target = strtolower($target);

        $region->removeOwner($target);
        unset($this->owners[$target][array_search($region, $this->owners[$target])]);
        if (empty($this->owners[$target])) unset($this->owners[$target]);
    }
}
