<?php
declare(strict_types=1);

namespace sergeydertan\sregionprotector\ui;

use http\Exception\RuntimeException;

abstract class UIType
{
    const CHEST = 0;
    const FORM = 1;

    private function __construct()
    {
    }

    public static function typeFromString(string $name): int
    {
        switch (strtolower($name)) {
            case "chest":
                return self::CHEST;
                break;
            case "form":
                return self::FORM;
                break;
        }
        throw new RuntimeException("Unknown UI type");
    }
}
