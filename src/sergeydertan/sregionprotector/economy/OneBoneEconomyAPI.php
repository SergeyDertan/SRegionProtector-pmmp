<?php
declare(strict_types=1);

namespace sergeydertan\sregionprotector\economy;

use onebone\economyapi\EconomyAPI;
use pocketmine\Player;

final class OneBoneEconomyAPI implements AbstractEconomy
{
    public function addMoney(Player $player, float $amount): void
    {
        EconomyAPI::getInstance()->addMoney($player, $amount);
    }

    public function reduceMoney(Player $player, float $amount): void
    {
        EconomyAPI::getInstance()->reduceMoney($player, $amount);
    }

    public function getMoney(Player $player): float
    {
        return (int)EconomyAPI::getInstance()->myMoney($player);
    }
}
