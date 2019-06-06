<?php
declare(strict_types=1);

namespace sergeydertan\sregionprotector\command\creation;

use pocketmine\command\CommandSender;
use pocketmine\Player;
use sergeydertan\sregionprotector\command\SRegionProtectorCommand;
use sergeydertan\sregionprotector\region\selector\RegionSelector;

final class RegionExpandCommand extends SRegionProtectorCommand
{
    const EXPAND_UP = "up";
    const EXPAND_DOWN = "down";
    const EXPAND_RADIUS = "radius";
    /**
     * @var RegionSelector
     */
    private $regionSelector;

    public function __construct(RegionSelector $selector)
    {
        parent::__construct("rgexpand", "expand");

        $this->regionSelector = $selector;
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        if (!$this->checkPerm($sender)) return;

        if (!$this->isPlayer($sender)) return;
        /**
         * @var Player $sender
         */
        if (!$this->regionSelector->sessionExists($sender)) {
            $this->messenger->sendMessage($sender, "command.{$this->msg}.positions-required");
            return;
        }
        if (!$this->checkUsage($sender, 2, $args)) return;

        $session = $this->regionSelector->getSession($sender);

        if ($session->pos1 === null || $session->pos2 === null) {
            $this->messenger->sendMessage($sender, "command.{$this->msg}.positions-required");
            return;
        }

        if ($session->pos1->level !== $session->pos2->level) {
            $this->messenger->sendMessage($sender, "command.{$this->msg}.positions-in-different-worlds");
            return;
        }

        $y = (int)$args[0];
        if (strcasecmp(self::EXPAND_UP, $args[1]) === 0) {
            $session->expandUp($y);
        } else if (strcasecmp(self::EXPAND_DOWN, $args[1]) === 0) {
            $session->expandDown($y);
        } else if (strcasecmp(self::EXPAND_RADIUS, $args[1]) === 0) {
            $session->expandRadius($y);
        } else {
            $this->messenger->sendMessage($sender, "command.{$this->msg}.up-or-down");
            return;
        }
        $session->fixHeight();
        if ($this->regionSelector->hasBorders($sender)) {
            $this->regionSelector->removeBorders($sender, true);
            $this->regionSelector->showBorders($sender, $session->pos1, $session->pos2);
        }
        $this->messenger->sendMessage($sender, "command.{$this->msg}.success", ["@size"], [(string)$session->calculateSize()]);
    }
}
