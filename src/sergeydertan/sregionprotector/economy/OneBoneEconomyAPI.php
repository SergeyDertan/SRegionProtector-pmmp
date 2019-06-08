<?php
declare(strict_types=1);

namespace sergeydertan\sregionprotector\economy;

use onebone\economyapi\EconomyAPI;

final class OneBoneEconomyAPI implements AbstractEconomy
{
    public function addMoney(string $player, float $amount): void
    {
        EconomyAPI::getInstance()->addMoney($player, $amount);
    }

    public function reduceMoney(string $player, float $amount): void
    {
        EconomyAPI::getInstance()->reduceMoney($player, $amount);
    }

    public function getMoney(string $player): float
    {
        return (int)EconomyAPI::getInstance()->myMoney($player);
    }
}
