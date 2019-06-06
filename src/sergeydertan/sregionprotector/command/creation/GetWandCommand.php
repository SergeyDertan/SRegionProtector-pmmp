<?php
declare(strict_types=1);

namespace sergeydertan\sregionprotector\command\creation;

use pocketmine\command\CommandSender;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\Player;
use sergeydertan\sregionprotector\command\SRegionProtectorCommand;

final class GetWandCommand extends SRegionProtectorCommand
{
    public function __construct()
    {
        parent::__construct("wand");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if (!$this->checkPerm($sender)) return;

        if (!$this->isPlayer($sender)) return;
        /**
         * @var Player $sender
         */
        $sender->getInventory()->addItem(Item::get(ItemIds::WOODEN_AXE, 0, 1));
        $this->messenger->sendMessage($sender, "command.{$this->msg}.wand-given");
    }
}
