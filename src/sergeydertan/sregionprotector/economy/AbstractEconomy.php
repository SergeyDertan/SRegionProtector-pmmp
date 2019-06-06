<?php
declare(strict_types=1);

namespace sergeydertan\sregionprotector\economy;

use pocketmine\Player;

interface AbstractEconomy
{
    public function getMoney(Player $player): int;

    public function addMoney(Player $player, float $amount): void;

    public function reduceMoney(Player $player, float $amount): void;
}
