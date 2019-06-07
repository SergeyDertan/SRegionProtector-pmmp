<?php
declare(strict_types=1);

namespace sergeydertan\sregionprotector\command\manage;

use pocketmine\command\CommandSender;
use sergeydertan\sregionprotector\command\SRegionProtectorCommand;
use sergeydertan\sregionprotector\region\flags\RegionFlags;
use sergeydertan\sregionprotector\region\RegionManager;

final class CopyFlagsCommand extends SRegionProtectorCommand
{
    private $regionManager;

    public function __construct(RegionManager $regionManager)
    {
        parent::__construct("rgcopyflags", "copy-flags");
        $this->regionManager = $regionManager;
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        if (!$this->checkPerm($sender)) return;
        if (!$this->checkUsage($sender, 2, $args)) return;

        $source = $this->regionManager->getRegion($args[0]);
        $target = $this->regionManager->getRegion($args[1]);

        if ($source === null) {
            $this->messenger->sendMessage($sender, "command.{$this->msg}.invalid-source");
            return;
        }
        if ($target === null) {
            $this->messenger->sendMessage($sender, "command.{$this->msg}.invalid-target");
            return;
        }
        for ($i = 0; $i < RegionFlags::FLAG_AMOUNT; ++$i) {
            $target->setFlagState($i, $source->getFlagState($i));
        }
        $target->setSellFlag($source->getSellFlagPrice(), $source->getFlagState(RegionFlags::FLAG_SELL));
        $target->setTeleportFlag($source->getTeleportFlagPos(), $source->getFlagState(RegionFlags::FLAG_TELEPORT));

        $this->messenger->sendMessage($sender, "command.{$this->msg}.success", ["@source", "@target"], [$source->getName(), $target->getName()]);
    }
}
