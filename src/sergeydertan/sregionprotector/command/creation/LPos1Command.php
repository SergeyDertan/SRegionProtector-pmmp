<?php
declare(strict_types=1);

namespace sergeydertan\sregionprotector\command\creation;

use pocketmine\command\CommandSender;
use pocketmine\level\Position;
use pocketmine\Player;
use sergeydertan\sregionprotector\command\SRegionProtectorCommand;
use sergeydertan\sregionprotector\region\selector\RegionSelector;

final class LPos1Command extends SRegionProtectorCommand
{
    /**
     * @var RegionSelector
     */
    private $regionSelector;
    /**
     * @var int
     */
    private $maxRadius;

    public function __construct(RegionSelector $regionSelector, int $maxRadius)
    {
        parent::__construct("lpos1");
        $this->regionSelector = $regionSelector;
        $this->maxRadius = $maxRadius;
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        if (!$this->checkPerm($sender)) return;

        if (!$this->isPlayer($sender)) return;
        /**
         * @var Player $sender
         */
        $pos = $sender->getTargetBlock($this->maxRadius);
        if ($pos === null) {
            $this->messenger->sendMessage($sender, "command.{$this->msg}.fail", ["@radius"], [(string)$this->maxRadius]);
        } else {
            $this->regionSelector->getSession($sender)->pos1 = Position::fromObject($pos, $pos->level);
            $this->messenger->sendMessage($sender, "command.{$this->msg}.success");
        }
    }
}
