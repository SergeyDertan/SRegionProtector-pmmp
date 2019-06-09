<?php
declare(strict_types=1);

namespace sergeydertan\sregionprotector\economy;

interface AbstractEconomy
{
    public function getMoney(string $player): float;

    public function addMoney(string $player, float $amount): void;

    public function reduceMoney(string $player, float $amount): void;
}
