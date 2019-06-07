<?php
declare(strict_types=1);

namespace sergeydertan\sregionprotector\command\manage;

use pocketmine\command\CommandSender;
use pocketmine\Player;
use sergeydertan\sregionprotector\command\SRegionProtectorCommand;
use sergeydertan\sregionprotector\region\RegionManager;
use sergeydertan\sregionprotector\region\selector\RegionSelector;

final class RegionSelectCommand extends SRegionProtectorCommand
{
    /**
     * @var RegionManager
     */
    private $regionManager;
    /**
     * @var RegionSelector
     */
    private $selector;
    /**
     * @var int
     */
    private $maxBordersAmount;

    public function __construct(RegionManager $regionManager, RegionSelector $selector, int $maxBordersAmount)
    {
        parent::__construct("rgselect", "select");
        $this->regionManager = $regionManager;
        $this->selector = $selector;
        $this->maxBordersAmount = $maxBordersAmount;
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        if (!$this->isPlayer($sender)) return;
        if (!$this->checkPerm($sender)) return;
        if (!$this->checkUsage($sender, 1, $args)) return;

        $region = $this->regionManager->getRegion($args[0]);
        if ($region === null) {
            $this->messenger->sendMessage($sender, "command.{$this->msg}.region-doesnt-exists", ["@region"], [$args[0]]);
            return;
        }
        if (!$region->isLivesIn($sender->getName()) && !$sender->hasPermission("sregionprotector.region.select-other") && !$region->isSelling()) {
            $this->messenger->sendMessage($sender, "command.{$this->msg}.permission");
            return;
        }
        /**
         * @var Player $sender
         */
        if (strcasecmp($region->getLevel(), $sender->level->getName()) !== 0) {
            $this->messenger->sendMessage($sender, "command.{$this->msg}.different-worlds");
            return;
        }
        if ($this->selector->calculateEdgesLength(
                $region->getMin(),
                $region->getMax()
            ) > $this->maxBordersAmount) {
            $this->messenger->sendMessage($sender, "command.{$this->msg}.too-long");
            return;
        }
        $this->selector->showBorders($sender, $region->getMin(), $region->getMax());
        $this->messenger->sendMessage($sender, "command.{$this->msg}.success");
    }
}
