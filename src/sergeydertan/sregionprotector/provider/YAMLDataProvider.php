<?php
declare(strict_types=1);

namespace sergeydertan\sregionprotector\provider;

use pocketmine\utils\Config;
use sergeydertan\sregionprotector\main\SRegionProtectorMain;
use sergeydertan\sregionprotector\region\flags\flag\RegionSellFlag;
use sergeydertan\sregionprotector\region\flags\flag\RegionTeleportFlag;
use sergeydertan\sregionprotector\region\flags\RegionFlags;
use sergeydertan\sregionprotector\region\Region;
use sergeydertan\sregionprotector\util\Utils;

final class YAMLDataProvider extends DataProvider
{

    public function saveRegion(Region $region): void
    {
        $data = [];

        $data["name"] = $region->getName();

        $data["min-x"] = $region->getMinX();
        $data["min-y"] = $region->getMinY();
        $data["min-z"] = $region->getMinZ();

        $data["max-x"] = $region->getMaxX();
        $data["max-y"] = $region->getMaxY();
        $data["max-z"] = $region->getMaxZ();

        $data["owners"] = $region->getOwners();
        $data["members"] = $region->getMembers();

        $data["creator"] = $region->getCreator();

        $data["level"] = $region->getLevel();

        $data["priority"] = $region->getPriority();

        $flags = [];
        foreach ($region->getFlags() as $id => $flag) {
            $flagData = [];
            $flagData["state"] = $flag->state;
            if ($flag instanceof RegionSellFlag) {
                $flagData["price"] = $flag->price;
            } elseif ($flag instanceof RegionTeleportFlag) {
                if ($flag->position != null) {
                    $flagData["x"] = $flag->position->x;
                    $flagData["y"] = $flag->position->y;
                    $flagData["z"] = $flag->position->z;
                }
                $flagData["level"] = $flag->level;
            }
            $flags[RegionFlags::getFlagName($id)] = $flagData;
        }
        $data["flags"] = $flags;
        $file = new Config(SRegionProtectorMain::getInstance()->getRegionsFolder() . strtolower($region->getName()) . ".yml", Config::YAML);
        $file->setAll($data);
        $file->save();
    }

    public function loadRegion(string $name): array
    {
        return (new Config(SRegionProtectorMain::getInstance()->getRegionsFolder() . strtolower($name) . ".yml", Config::YAML))->getAll();
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

    public function removeRegion(string $name): void
    {
        unlink(SRegionProtectorMain::getInstance()->getRegionsFolder() . strtolower($name) . ".yml");
    }

    public function getType(): int
    {
        return self::YAML;
    }
}
