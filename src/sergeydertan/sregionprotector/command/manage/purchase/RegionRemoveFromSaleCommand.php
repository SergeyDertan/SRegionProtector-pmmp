<?php
declare(strict_types=1);

namespace sergeydertan\sregionprotector\command\manage\purchase;

use pocketmine\command\CommandSender;
use sergeydertan\sregionprotector\command\SRegionProtectorCommand;
use sergeydertan\sregionprotector\region\RegionManager;

final class RegionRemoveFromSaleCommand extends SRegionProtectorCommand
{
    /**
     * @var RegionManager
     */
    private $regionManager;

    public function __construct(RegionManager $regionManager)
    {
        parent::__construct("rgremovefromsell", "remove-from-sell");
        $this->regionManager = $regionManager;
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        if (!$this->checkPerm($sender)) return;
        if (!$this->checkUsage($sender, 1, $args)) return;
        $region = $this->regionManager->getRegion($args[0]);
        if ($region === null) {
            $this->messenger->sendMessage($sender, "command.{$this->msg}.wrong-target");
            return;
        }
        if (!$region->isCreator($sender->getName()) && !$sender->hasPermission("sregionprotector.admin")) {
            $this->messenger->sendMessage($sender, "command.{$this->msg}.not-creator");
            return;
        }
        $region->setSellFlag(0, false);
        $this->messenger->sendMessage($sender, "command.{$this->msg}.success", ["@region"], [$region->getName()]);
    }
}
