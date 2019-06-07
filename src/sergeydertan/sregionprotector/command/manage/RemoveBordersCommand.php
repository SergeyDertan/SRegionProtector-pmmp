<?php
declare(strict_types=1);

namespace sergeydertan\sregionprotector\command\manage;

use pocketmine\command\CommandSender;
use pocketmine\Player;
use sergeydertan\sregionprotector\command\SRegionProtectorCommand;
use sergeydertan\sregionprotector\region\selector\RegionSelector;

final class RemoveBordersCommand extends SRegionProtectorCommand
{
    /**
     * @var RegionSelector
     */
    private $selector;

    public function __construct(RegionSelector $selector)
    {
        parent::__construct("rgremoveborders", "remove-borders");
        $this->selector = $selector;
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        if (!$this->checkPerm($sender)) return;
        if (!$this->isPlayer($sender)) return;
        $this->messenger->sendMessage($sender, "command.{$this->msg}.success");
        /**
         * @var Player $sender
         */
        $this->selector->removeBorders($sender);
    }
}
