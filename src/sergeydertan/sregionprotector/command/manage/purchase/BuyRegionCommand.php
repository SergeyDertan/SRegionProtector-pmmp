<?php
declare(strict_types=1);

namespace sergeydertan\sregionprotector\command\manage\purchase;

use pocketmine\command\CommandSender;
use pocketmine\Player;
use sergeydertan\sregionprotector\command\SRegionProtectorCommand;
use sergeydertan\sregionprotector\economy\AbstractEconomy;
use sergeydertan\sregionprotector\region\RegionManager;

final class BuyRegionCommand extends SRegionProtectorCommand
{
    /**
     * @var RegionManager
     */
    private $regionManager;
    /**
     * @var AbstractEconomy
     */
    private $economy;

    public function __construct(RegionManager $regionManager, ?AbstractEconomy $economy)
    {
        parent::__construct("rgbuy", "buy");
        $this->regionManager = $regionManager;
        $this->economy = $economy;
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        if ($this->economy === null) {
            $this->messenger->sendMessage($sender, "command.{$this->msg}.no-economy");
            return;
        }
        if (!$this->isPlayer($sender)) return;
        if (!$this->checkPerm($sender)) return;
        if (!$this->checkUsage($sender, 2, $args)) return;

        $target = $this->regionManager->getRegion($args[0]);

        if ($target === null) {
            $this->messenger->sendMessage($sender, "command.{$this->msg}.wrong-target", ["@region"], [$args[0]]);
            return;
        }
        if ($target->isCreator($sender->getName())) {
            $this->messenger->sendMessage($sender, "command.{$this->msg}.cant-buy-your-self");
            return;
        }
        if (!$target->isSelling()) {
            $this->messenger->sendMessage($sender, "command.{$this->msg}.doesnt-selling");
            return;
        }
        $price = $target->getSellFlagPrice();
        /**
         * @var Player $sender
         */
        if ($price > $this->economy->getMoney($sender->getName())) {
            $this->messenger->sendMessage($sender, "command.{$this->msg}.no-money");
            return;
        }
        if ($price !== (int)$args[1]) {
            $this->messenger->sendMessage($sender, "command.{$this->msg}.wrong-price");
            return;
        }
        $this->economy->addMoney($target->getCreator(), $price);
        $this->economy->reduceMoney($sender->getName(), $price);
        $this->regionManager->changeRegionOwner($target, $sender->getName());
        $this->messenger->sendMessage($sender, "command.{$this->msg}.success", ["@region", "@price"], [$target->getName(), (string)$price]);
    }
}
