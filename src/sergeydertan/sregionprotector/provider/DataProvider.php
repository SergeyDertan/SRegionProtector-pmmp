<?php
declare(strict_types=1);

namespace sergeydertan\sregionprotector\provider;

use sergeydertan\sregionprotector\region\Region;

abstract class DataProvider
{
    const UNKNOWN = -1;
    const YAML = 0;

    public static function providerFromString(string $name): int
    {
        switch (strtolower($name)) {
            default:
                return self::UNKNOWN;
                break;
            case "yaml":
            case "yml":
                return self::YAML;
                break;
        }
    }

    public abstract function saveRegion(Region $region): void;

    public abstract function loadRegion(string $name): array;

    /**
     * id(nothing) -> region data array
     * @return array[]
     */
    public abstract function loadRegionList(): array;

    public abstract function getType(): int;

    public abstract function getName(): string;

    public abstract function removeRegion(string $name): void;

    public function close(): void
    {
    }
}
