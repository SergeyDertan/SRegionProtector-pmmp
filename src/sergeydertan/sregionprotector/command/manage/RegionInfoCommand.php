<?php
declare(strict_types=1);

namespace sergeydertan\sregionprotector\command\manage;

use pocketmine\command\CommandSender;
use pocketmine\Player;
use sergeydertan\sregionprotector\command\SRegionProtectorCommand;
use sergeydertan\sregionprotector\region\chunk\ChunkManager;
use sergeydertan\sregionprotector\region\flags\RegionFlags;
use sergeydertan\sregionprotector\region\Region;
use sergeydertan\sregionprotector\region\RegionManager;
use sergeydertan\sregionprotector\settings\RegionSettings;

final class RegionInfoCommand extends SRegionProtectorCommand
{
    /**
     * @var RegionManager
     */
    private $regionManager;
    /**
     * @var ChunkManager
     */
    private $chunkManager;
    /**
     * @var RegionSettings
     */
    private $regionSettings;

    public function __construct(RegionManager $regionManager, ChunkManager $chunkManager, RegionSettings $regionSettings)
    {
        parent::__construct("rginfo", "info");
        $this->regionManager = $regionManager;
        $this->chunkManager = $chunkManager;
        $this->regionSettings = $regionSettings;
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        if (!$this->checkPerm($sender)) return;
        if (empty($args)) {
            if (!$sender instanceof Player) {
                $r = [];
                $this->checkUsage($sender, 1, $r);
                return;
            }
            $region = $this->chunkManager->getRegion($sender, $sender->level->getName());
            if ($region === null) {
                $this->messenger->sendMessage($sender, "command.{$this->msg}.region-doesnt-exists", ["@region"], [""]);
                return;
            }
            $this->showRegionInfo($sender, $region);
            return;
        }
        $region = $this->regionManager->getRegion($args[0]);
        if ($region === null) {
            $this->messenger->sendMessage($sender, "command.{$this->msg}.region-doesnt-exists", ["@region"], [$args[0]]);
            return;
        }
        $this->showRegionInfo($sender, $region);
    }

    private function showRegionInfo(CommandSender $target, Region $region): void
    {
        if ($target instanceof Player && !$region->isSelling()) {
            if (!$region->isLivesIn($target->getName()) && !$target->hasPermission("sregionprotector.info.other") && !$target->hasPermission("sregionprotector.admin")) {
                $this->messenger->sendMessage($target, "command.{$this->msg}.permission");
                return;
            }
        }
        $name = $region->getName();
        $level = $region->getLevel();
        $owner = $region->getCreator();
        $owners = implode(", ", $region->getOwners());
        $members = implode(", ", $region->getMembers());
        $size = (int)$region->getSize();
        $flags = [];
        for ($i = 0; $i < RegionFlags::FLAG_AMOUNT; ++$i) {
            if (!$this->regionSettings->getFlagStatus()[$i] || !$this->regionSettings->getDisplay()[$i]) continue;
            $ad = $region->getFlagState($i) === RegionFlags::getStateFromString("allow", $i) ? "allow" : "deny";
            $flags[] = RegionFlags::getFlagName($i) . ": " . $ad;
        }
        $this->messenger->sendMessage($target, "command.{$this->msg}.info",
            ["@region", "@creator", "@level", "@owners", "@members", "@flags", "@size", "@priority"],
            [$name, $owner, $level, $owners, $members, implode(", ", $flags), (string)$size, (string)$region->getPriority()]
        );
    }
}
