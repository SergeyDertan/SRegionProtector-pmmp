<?php
declare(strict_types=1);

namespace sergeydertan\sregionprotector\region;

abstract class RegionGroup
{
    const INVALID = -1;
    const CREATOR = 0;
    const OWNER = 1;
    const MEMBER = 2;

    private function __construct()
    {
    }

    public static function valueOf(string $name): int
    {
        switch (strtolower($name)) {
            default:
                return self::INVALID;
                break;
            case "creator":
                return self::CREATOR;
                break;
            case "owner":
                return self::OWNER;
                break;
            case "member":
                return self::MEMBER;
                break;
        }
    }
}
