<?php
declare(strict_types=1);

namespace sergeydertan\sregionprotector\command\manage;

use pocketmine\command\CommandSender;
use pocketmine\Player;
use sergeydertan\sregionprotector\command\SRegionProtectorCommand;
use sergeydertan\sregionprotector\region\RegionManager;

final class RegionRemoveCommand extends SRegionProtectorCommand
{
    /**
     * @var RegionManager
     */
    private $regionManager;

    public function __construct(RegionManager $regionManager)
    {
        parent::__construct("rgremove", "remove");
        $this->regionManager = $regionManager;
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        if (!$this->checkPerm($sender)) return;
        if (!$this->checkUsage($sender, 1, $args)) return;
        $region = $this->regionManager->getRegion($args[0]);
        if ($region === null) {
            $this->messenger->sendMessage($sender, "command.{$this->msg}.region-doesnt-exists", ["@region"], [$args[0]]);
            return;
        }
        if (!$sender->hasPermission("sregionprotector.admin") && ($sender instanceof Player && !$region->isCreator($sender->getName()))) {
            $this->messenger->sendMessage($sender, "command.{$this->msg}.permission");
            return;
        }
        $this->regionManager->removeRegion($region);
        $this->messenger->sendMessage($sender, "command.{$this->msg}.region-removed", ["@region"], [$region->getName()]);
    }
}
