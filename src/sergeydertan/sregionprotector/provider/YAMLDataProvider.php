<?php
declare(strict_types=1);

namespace sergeydertan\sregionprotector\provider;

use pocketmine\level\Location;
use pocketmine\utils\Config;
use sergeydertan\sregionprotector\main\SRegionProtectorMain;
use sergeydertan\sregionprotector\region\flags\flag\RegionSellFlag;
use sergeydertan\sregionprotector\region\flags\flag\RegionTeleportFlag;
use sergeydertan\sregionprotector\region\flags\RegionFlags;
use sergeydertan\sregionprotector\region\Region;
use sergeydertan\sregionprotector\util\Tags;
use sergeydertan\sregionprotector\util\Utils;

final class YAMLDataProvider extends DataProvider
{

    public function saveRegion(Region $region): void
    {
        $data = [];

        $data[Tags::NAME_TAG] = $region->getName();

        $data[Tags::MIN_X_TAG] = $region->getMinX();
        $data[Tags::MIN_Y_TAG] = $region->getMinY();
        $data[Tags::MIN_Z_TAG] = $region->getMinZ();

        $data[Tags::MAX_X_TAG] = $region->getMaxX();
        $data[Tags::MAX_Y_TAG] = $region->getMaxY();
        $data[Tags::MAX_Z_TAG] = $region->getMaxZ();

        $data[Tags::OWNERS_TAG] = $region->getOwners();
        $data[Tags::MEMBERS_TAG] = $region->getMembers();

        $data[Tags::CREATOR_TAG] = $region->getCreator();

        $data[Tags::LEVEL_TAG] = $region->getLevel();

        $data[Tags::PRIORITY_TAG] = $region->getPriority();

        $flags = [];
        foreach ($region->getFlags() as $id => $flag) {
            $flagData = [];
            $flagData[Tags::STATE_TAG] = $flag->state;
            if ($flag instanceof RegionSellFlag) {
                $flagData[Tags::PRICE_TAG] = $flag->price;
            } elseif ($flag instanceof RegionTeleportFlag) {
                if ($flag->position !== null) {
                    $flagData[Tags::X_TAG] = $flag->position->x;
                    $flagData[Tags::Y_TAG] = $flag->position->y;
                    $flagData[Tags::Z_TAG] = $flag->position->z;

                    if ($flag->position instanceof Location) {
                        $flagData[Tags::YAW_TAG] = $flag->position->yaw;
                        $flagData[Tags::PITCH_TAG] = $flag->position->pitch;
                    }
                }
                $flagData[Tags::LEVEL_TAG] = $flag->level;
            }
            $flags[RegionFlags::getFlagName($id)] = $flagData;
        }
        $data[Tags::FLAGS_TAG] = $flags;
        $file = new Config(SRegionProtectorMain::getInstance()->getRegionsFolder() . strtolower($region->getName()) . ".yml", Config::YAML);
        $file->setAll($data);
        $file->save();
    }

    /**
     * @return array[]
     */
    public function loadRegionList(): array
    {
        $regions = [];

        $dir = opendir(SRegionProtectorMain::getInstance()->getRegionsFolder());
        $folder = SRegionProtectorMain::getInstance()->getRegionsFolder();
        while (($file = readdir($dir)) !== false) {
            if (!Utils::endsWith($file, ".yml") || !is_file($folder . $file)) continue;
            $regions[] = $this->loadRegion(str_replace(".yml", "", $file));
        }
        return $regions;
    }

    public function loadRegion(string $name): array
    {
        return (new Config(SRegionProtectorMain::getInstance()->getRegionsFolder() . strtolower($name) . ".yml", Config::YAML))->getAll();
    }

    public function removeRegion(string $name): void
    {
        @unlink(SRegionProtectorMain::getInstance()->getRegionsFolder() . strtolower($name) . ".yml");
    }

    public function getType(): int
    {
        return self::YAML;
    }

    public function getName(): string
    {
        return "YAML";
    }
}
