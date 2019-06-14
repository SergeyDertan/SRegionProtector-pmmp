<?php
declare(strict_types=1);

namespace sergeydertan\sregionprotector\region\flags;

use http\Exception\RuntimeException;
use pocketmine\permission\Permissible;
use ReflectionClass;
use sergeydertan\sregionprotector\region\flags\flag\RegionFlag;
use sergeydertan\sregionprotector\region\flags\flag\RegionSellFlag;
use sergeydertan\sregionprotector\region\flags\flag\RegionTeleportFlag;

abstract class RegionFlags
{
    const FLAG_INVALID = -1;
    const FLAG_PLACE = 0;
    const FLAG_BREAK = 29;
    const FLAG_INTERACT = 1;
    const FLAG_USE = 2;
    const FLAG_PVP = 3;
    const FLAG_EXPLODE = 4;
    const FLAG_LIGHTER = 5;
    const FLAG_MAGIC_ITEM_USE = 6;
    const FLAG_HEAL = 7;
    const FLAG_INVINCIBLE = 8;
    const FLAG_TELEPORT = 9;
    const FLAG_SELL = 10;
    const FLAG_POTION_LAUNCH = 11;
    const FLAG_MOVE = 12;
    const FLAG_LEAVES_DECAY = 13;
    const FLAG_ITEM_DROP = 14;
    const FLAG_SEND_CHAT = 15;
    const FLAG_RECEIVE_CHAT = 16;
    const FLAG_HEALTH_REGEN = 17;
    const FLAG_MOB_DAMAGE = 18;
    const FLAG_MOB_SPAWN = 19;
    const FLAG_CROPS_DESTROY = 20;
    const FLAG_REDSTONE = 21;
    const FLAG_ENDER_PEARL = 22;
    const FLAG_EXPLODE_BLOCK_BREAK = 23;
    const FLAG_LIGHTNING_STRIKE = 24;
    const FLAG_FIRE = 25;
    const FLAG_LIQUID_FLOW = 26;
    const FLAG_CHEST_ACCESS = 27; //lava & water spread
    const FLAG_SLEEP = 28;
    const FLAG_CHUNK_LOADER = 30;
    const FLAG_SMART_DOORS = 31;
    const FLAG_MINEFARM = 32;
    const FLAG_FALL_DAMAGE = 33;
    const FLAG_NETHER_PORTAL = 34;
    const FLAG_FRAME_ITEM_DROP = 35;
    const FLAG_BUCKET_EMPTY = 36;
    const FLAG_BUCKET_FILL = 37;

    const FLAG_AMOUNT = 38;
    /**
     * @var RegionFlag[]
     */
    private static $defaults = [];
    /**
     * @var string[]
     */
    private static $permissions = [];
    /**
     * flags names
     *
     * @var string[]
     */
    private static $name = [];
    /**
     * flags ids
     * @var int[]
     */
    private static $id = [];
    /**
     * true if "allow" means that flag should be disabled
     *
     * @var boolean[]
     */
    private static $state = [];

    private function __construct()
    {
    }

    /**
     * @param bool[] $flagsDefault
     */
    public static function initDefaults(array $flagsDefault): void
    {
        for ($i = 0; $i < self::FLAG_AMOUNT; ++$i) {
            self::$defaults[$i] = new RegionFlag($flagsDefault[$i]);
        }
        self::$defaults[self::FLAG_TELEPORT] = new RegionTeleportFlag($flagsDefault[self::FLAG_TELEPORT]);
        self::$defaults[self::FLAG_SELL] = new RegionSellFlag($flagsDefault[self::FLAG_SELL]);
    }

    public static function init(): void
    {
        $clazz = new ReflectionClass(RegionFlags::class);
        foreach ($clazz->getConstants() as $key => $value) {
            if ($key === "FLAG_AMOUNT" || $key === "FLAG_INVALID") continue;

            $name = str_replace("FLAG_", "", $key);
            $name = strtolower($name);
            $name = str_replace("_", "-", $name);
            self::$name[$value] = $name;

            self::$id[$name] = $value;
            self::$id[str_replace("-", "_", $name)] = $value;
            self::$id[str_replace("-", "", $name)] = $value;
        }

        foreach (self::$name as $id => $name) {
            self::$permissions[$id] = "sregionprotector.region.flag." . str_replace("-", "_", $name);
        }

        for ($i = 0; $i < self::FLAG_AMOUNT; ++$i) {
            self::$state[$i] = true;
        }
        self::$state[self::FLAG_HEAL] = false;
        self::$state[self::FLAG_INVINCIBLE] = false;
        self::$state[self::FLAG_TELEPORT] = false;
        self::$state[self::FLAG_SELL] = false;
        self::$state[self::FLAG_CHUNK_LOADER] = false;
        self::$state[self::FLAG_SMART_DOORS] = false;
        self::$state[self::FLAG_MINEFARM] = false;
        self::$state[self::FLAG_FALL_DAMAGE] = false;
        self::$state[self::FLAG_EXPLODE_BLOCK_BREAK] = false;
    }

    public static function getFlagId(string $name): int
    {
        return self::$id[strtolower($name)];
    }

    public static function getFlagPermission(int $flag): string
    {
        return self::$permissions[$flag];
    }

    public static function getFlagName(int $flag): string
    {
        return self::$name[$flag];
    }

    public static function getStateFromString(string $state, int $flag): bool
    {
        if (strcasecmp($state, "allow")) return !self::$state[$flag];
        if (strcasecmp($state, "deny")) return self::$state[$flag];
        throw new RuntimeException("Wrong state");
    }

    /**
     * @param RegionFlag[] $flags
     */
    public static function fixMissingFlags(array &$flags): void
    {
        for ($i = count($flags); $i < self::FLAG_AMOUNT; ++$i) {
            $flags[$i] = clone self::$defaults[$i];
        }
    }

    public static function hasFlagPermission(Permissible $target, int $flag): bool
    {
        return $target->hasPermission(self::$permissions[$flag]);
    }

    public static function getDefaultFlagState(int $flag): bool
    {
        return self::$defaults[$flag]->state;
    }

    /**
     * @return RegionFlag[]
     */
    public static function getDefaultFlagList(): array
    {
        $list = [];
        foreach (self::$defaults as $id => $flag) {
            $list[$id] = clone $flag;
        }
        return $list;
    }
}
