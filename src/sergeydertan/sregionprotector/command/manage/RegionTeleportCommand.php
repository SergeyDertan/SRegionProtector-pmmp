<?php
declare(strict_types=1);

namespace sergeydertan\sregionprotector\command\manage;

use pocketmine\command\CommandSender;
use pocketmine\Player;
use sergeydertan\sregionprotector\command\SRegionProtectorCommand;
use sergeydertan\sregionprotector\region\flags\RegionFlags;
use sergeydertan\sregionprotector\region\RegionManager;

final class RegionTeleportCommand extends SRegionProtectorCommand
{
    /**
     * @var RegionManager
     */
    private $regionManager;

    public function __construct(RegionManager $regionManager)
    {
        parent::__construct("rgteleport", "teleport");
        $this->regionManager = $regionManager;
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        if (!$this->checkPerm($sender)) return;
        if (!$this->checkUsage($sender, 1, $args)) return;
        if (!$this->isPlayer($sender)) return;
        $region = $this->regionManager->getRegion($args[0]);
        if ($region === null) {
            $this->messenger->sendMessage($sender, "command.{$this->msg}.region-doesnt-exists");
            return;
        }
        if (!$region->isLivesIn($sender->getName()) && !$sender->hasPermission("sregionprotector.admin")) {
            $this->messenger->sendMessage($sender, "command.{$this->msg}.permission");
            return;
        }
        if (!$region->getFlagState(RegionFlags::FLAG_TELEPORT) || $region->getTeleportFlagPos() === null) {
            $this->messenger->sendMessage($sender, "command.{$this->msg}.teleport-disabled");
            return;
        }
        /**
         * @var Player $sender
         */
        $sender->teleport($region->getTeleportFlagPos());
        $this->messenger->sendMessage($sender, "command.{$this->msg}.teleport", ["@region"], [$region->getName()]);
    }
}
