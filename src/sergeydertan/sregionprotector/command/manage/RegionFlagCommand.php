<?php
declare(strict_types=1);

namespace sergeydertan\sregionprotector\command\manage;

use pocketmine\command\CommandSender;
use pocketmine\Player;
use sergeydertan\sregionprotector\command\SRegionProtectorCommand;
use sergeydertan\sregionprotector\region\flags\RegionFlags;
use sergeydertan\sregionprotector\region\RegionManager;

final class RegionFlagCommand extends SRegionProtectorCommand
{
    /**
     * @var RegionManager
     */
    private $regionManager;
    /**
     * @var bool[]
     */
    private $flagStatus;

    public function __construct(RegionManager $regionManager, array $flagStatus)
    {
        parent::__construct("rgflag", "flag");
        $this->regionManager = $regionManager;
        $this->flagStatus = $flagStatus;
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        if (!$this->checkPerm($sender)) return;
        if (!$this->checkUsage($sender, 3, $args)) return;

        $flag = RegionFlags::getFlagId($args[1]);
        if ($flag === RegionFlags::FLAG_INVALID) {
            $this->messenger->sendMessage($sender, "command.{$this->msg}.incorrect-flag");
            return;
        }

        if (strcasecmp($args[2], "allow") !== 0 && strcasecmp($args[2], "deny") !== 0) {
            $this->messenger->sendMessage($sender, "command.{$this->msg}.wrong-state");
            return;
        }

        $region = $this->regionManager->getRegion($args[0]);
        if ($region === null) {
            $this->messenger->sendMessage($sender, "command.{$this->msg}.region-doesnt-exists");
            return;
        }
        if ($sender instanceof Player && !$sender->hasPermission("sregionprotector.admin") && !$region->isOwner($sender->getName(), true)) {
            $this->messenger->sendMessage($sender, "command.{$this->msg}.permission");
            return;
        }
        if (!RegionFlags::hasFlagPermission($sender, $flag)) {
            $this->messenger->sendMessage($sender, "command.{$this->msg}.permission");
            return;
        }
        if ($flag === RegionFlags::FLAG_SELL) {
            $this->messenger->sendMessage($sender, "command.{$this->msg}.sell");
            return;
        }

        $state = RegionFlags::getStateFromString($args[2], $flag);
        if ($flag === RegionFlags::FLAG_TELEPORT) {
            if ($state) {
                if (!$sender instanceof Player) {
                    $this->messenger->sendMessage($sender, "command.{$this->msg}.teleport-flag-in-game");
                    return;
                }
                if (strcasecmp($region->getLevel(), $sender->level->getName()) !== 0 || !$region->isVectorInside($sender)) {
                    $this->messenger->sendMessage($sender, "command.{$this->msg}.teleport-should-be-in-region");
                    return;
                }
                $region->setTeleportFlag($sender->getLocation(), true);
            } else {
                $region->setTeleportFlag(null, false);
            }
        }
        if (!$this->flagStatus[$flag]) {
            $this->messenger->sendMessage($sender, "command.flag.disabled-warning");
        }
        $region->setFlagState($flag, $state);
        $this->messenger->sendMessage($sender, "command.{$this->msg}.flag." . ($state ? "enabled" : "disabled"), ["@region", "@flag"], [$region->getName(), $args[1]]);
    }
}
