<?php
declare(strict_types=1);

namespace sergeydertan\sregionprotector\command\creation;

use pocketmine\command\CommandSender;
use pocketmine\Player;
use sergeydertan\sregionprotector\command\SRegionProtectorCommand;
use sergeydertan\sregionprotector\region\selector\RegionSelector;

final class ShowBorderCommand extends SRegionProtectorCommand
{
    /**
     * @var RegionSelector
     */
    private $regionSelector;
    /**
     * @var int
     */
    private $maxBorders;

    public function __construct(RegionSelector $selector, int $maxBorders)
    {
        parent::__construct("rgshowborder", "show-border");

        $this->regionSelector = $selector;
        $this->maxBorders = $maxBorders;
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        if (!$this->checkPerm($sender)) return;

        if (!$this->isPlayer($sender)) return;
        /**
         * @var Player $sender
         */
        if (!$this->regionSelector->sessionExists($sender)) {
            $this->messenger->sendMessage($sender, "command.{$this->msg}.select-first");
            return;
        }
        $session = $this->regionSelector->getSession($sender);

        if ($session->pos1 === null || $session->pos2 === null) {
            $this->messenger->sendMessage($sender, "command.{$this->msg}.positions-required");
            return;
        }
        if ($session->pos1->level !== $session->pos2->level) {
            $this->messenger->sendMessage($sender, "command.{$this->msg}.positions-in-different-worlds");
            return;
        }
        if ($this->regionSelector->calculateEdgesLength($session->pos1, $session->pos2) > $this->maxBorders) {
            $this->messenger->sendMessage($sender, "command.{$this->msg}.too-long");
            return;
        }
        $this->messenger->sendMessage($sender, "command.{$this->msg}.success");
        $this->regionSelector->showBorders($sender, $session->pos1, $session->pos2);
    }
}
