<?php
declare(strict_types=1);

namespace sergeydertan\sregionprotector\command\creation;

use pocketmine\command\CommandSender;
use pocketmine\level\Position;
use pocketmine\Player;
use sergeydertan\sregionprotector\command\SRegionProtectorCommand;
use sergeydertan\sregionprotector\region\selector\RegionSelector;

final class Pos2Command extends SRegionProtectorCommand
{
    /**
     * @var RegionSelector
     */
    private $regionSelector;

    public function __construct(RegionSelector $regionSelector)
    {
        parent::__construct("pos2");
        $this->regionSelector = $regionSelector;
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        if (!$this->checkPerm($sender)) return;

        if (!$this->isPlayer($sender)) return;

        /**
         * @var Player $sender
         */
        if (count($args) >= 3) {
            $this->regionSelector->getSession($sender)->pos2 = new Position(
                (float)$args[0],
                (float)$args[1],
                (float)$args[2],
                $sender->level
            );
        } else {
            $this->regionSelector->getSession($sender)->pos2 = clone $sender->getPosition();
        }
        $this->messenger->sendMessage($sender, "command.{$this->msg}.pos-set");
    }
}
