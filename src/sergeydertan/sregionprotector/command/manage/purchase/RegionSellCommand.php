<?php
declare(strict_types=1);

namespace sergeydertan\sregionprotector\command\manage\purchase;

use pocketmine\command\CommandSender;
use sergeydertan\sregionprotector\command\SRegionProtectorCommand;
use sergeydertan\sregionprotector\region\RegionManager;

final class RegionSellCommand extends SRegionProtectorCommand
{
    /**
     * @var RegionManager
     */
    private $regionManager;

    public function __construct(RegionManager $regionManager)
    {
        parent::__construct("rgsell", "sell");
        $this->regionManager = $regionManager;
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        if (!$this->checkPerm($sender)) return;
        if (!$this->checkUsage($sender, 2, $args)) return;
        $region = $this->regionManager->getRegion($args[0]);
        if ($region === null) {
            $this->messenger->sendMessage($sender, "command.{$this->msg}.wrong-target");
            return;
        }
        if (!$region->isCreator($sender->getName()) && !$sender->hasPermission("sregionprotector.admin")) {
            $this->messenger->sendMessage($sender, "command.{$this->msg}.not-creator");
            return;
        }
        if ($this->regionManager->checkOverlap($region->getMin()->add(-1, -1, -1), $region->getMax()->add(1, 1, 1), $region->getLevel(), "", false, $region)) {
            $this->messenger->sendMessage($sender, "command.{$this->msg}.overlap");
            return;
        }
        $price = (int)$args[1];
        if ($price < 0) {
            $this->messenger->sendMessage($sender, "command.{$this->msg}.min-price");
            return;
        }
        $region->setSellFlag($price, true);
        $this->messenger->sendMessage($sender, "command.{$this->msg}.success", ["@region", "@price"], [$region->getName(), (int)$price]);
    }
}
