<?php
declare(strict_types=1);

namespace sergeydertan\sregionprotector\settings;

use pocketmine\permission\Permissible;
use sergeydertan\sregionprotector\blockentity\BlockEntityHealer;
use sergeydertan\sregionprotector\region\flags\RegionFlags;
use sergeydertan\sregionprotector\util\Utils;

final class RegionSettings
{
    /**
     * @var boolean[]
     */
    private $flagStatus;
    /**
     * default flag value while creating region
     * @var boolean[]
     */
    private $defaultFlags;

    /**
     * check if player will see the message
     * @var boolean[]
     */
    private $needMessage;

    /**
     * check if flag should be shown in the info command
     * @var boolean[]
     */
    private $display;

    /**
     * if true after adding new flag to the region it will be saved
     * @var boolean
     */
    private $saveNewFlags;

    /**
     * @var int
     */
    private $maxRegionNameLength;
    /**
     * @var int
     */
    private $minRegionNameLength;

    /**
     * @var int
     */
    private $defaultAmount;
    /**
     * @var int
     */
    private $defaultSize;

    /**
     * in ticks, 1 second = 20 ticks
     * @var int
     */
    private $healFlagHealDelay;
    /**
     * @var float
     */
    private $healFlagHealAmount;

    public function __construct(array $cnf, array $rgCnf)
    {
        RegionFlags::init();
        $this->loadFlagStatus($cnf);
        $this->loadDefaultFlags($rgCnf);
        $this->loadHealFlagSettings($rgCnf);
        $this->loadMessages($rgCnf);
        $this->loadDisplaySettings($cnf);

        $this->saveNewFlags = (bool)$cnf["save-new-flags"];

        $this->maxRegionNameLength = (int)$rgCnf["max-region-name-length"];
        $this->minRegionNameLength = (int)$rgCnf["min-region-name-length"];

        $this->defaultAmount = (int)$cnf["default-max-region-amount"];
        $this->defaultSize = (int)$cnf["default-max-region-size"];
    }

    private function loadFlagStatus(array $cnf): void
    {
        for ($i = 0; $i < RegionFlags::FLAG_AMOUNT; ++$i) {
            $this->flagStatus[$i] = false;
        }
        foreach ($cnf["active-flags"] as $name => $value) {
            if (RegionFlags::getFlagId($name) === RegionFlags::FLAG_INVALID) continue;
            $this->flagStatus[RegionFlags::getFlagId($name)] = (bool)$value;
        }
    }

    private function loadDefaultFlags(array $rgCnf): void
    {
        for ($i = 0; $i < RegionFlags::FLAG_AMOUNT; ++$i) {
            $this->defaultFlags[$i] = false;
        }
        foreach ($rgCnf["default-flags"] as $name => $value) {
            if (RegionFlags::getFlagId($name) === RegionFlags::FLAG_INVALID) continue;
            $this->defaultFlags[RegionFlags::getFlagId($name)] = (bool)$value;
        }

        RegionFlags::initDefaults($this->defaultFlags);
    }

    private function loadHealFlagSettings(array $cnf): void
    {
        $this->healFlagHealDelay = (int)$cnf["heal-flag-heal-delay"];
        $this->healFlagHealAmount = (float)$cnf["heal-flag-heal-amount"];

        BlockEntityHealer::$HEAL_DELAY = $this->healFlagHealDelay;
        BlockEntityHealer::$HEAL_AMOUNT = $this->healFlagHealAmount;
        BlockEntityHealer::$FLAG_ENABLED = (bool)$this->flagStatus[RegionFlags::FLAG_HEAL];
    }

    private function loadMessages(array $rgCnf): void
    {
        for ($i = 0; $i < RegionFlags::FLAG_AMOUNT; ++$i) {
            $this->needMessage[$i] = true;
        }
        foreach ($rgCnf["need-message"] as $flag => $value) {
            if (RegionFlags::getFlagId($flag) === RegionFlags::FLAG_INVALID) continue;
            $this->needMessage[RegionFlags::getFlagId($flag)] = (bool)$value;
        }
    }

    private function loadDisplaySettings(array $cnf): void
    {
        for ($i = 0; $i < RegionFlags::FLAG_AMOUNT; ++$i) {
            $this->display[$i] = true;
        }
        foreach ($cnf["display"] as $flag => $value) {
            if (RegionFlags::getFlagId($flag) === RegionFlags::FLAG_INVALID) continue;
            $this->display[RegionFlags::getFlagId($flag)] = (bool)$value;
        }
    }

    public function hasSizePermission(Permissible $target, int $size): bool
    {
        if ($size < $this->defaultSize || $target->hasPermission("sregionprotector.region.size.*")) return true;
        foreach ($target->getEffectivePermissions() as $permission) {
            if (!Utils::startsWith($permission->getPermission(), "sregionprotector.region.size.")) continue;
            if ((int)str_replace("sregionprotector.region.size.", "", $permission->getPermission()) >= $size) return true;
        }
        return false;
    }

    public function hasAmountPermission(Permissible $target, int $amount): bool
    {
        if ($amount < $this->defaultAmount || $target->hasPermission("sregionprotector.region.amount.*")) return true;
        foreach ($target->getEffectivePermissions() as $permission) {
            if (!Utils::startsWith($permission->getPermission(), "sregionprotector.region.amount.")) continue;
            if ((int)str_replace("sregionprotector.region.amount.", "", $permission->getPermission()) >= $amount) return true;
        }
        return false;
    }

    public function isFlagEnabled(int $flagId): bool
    {
        return $this->flagStatus[$flagId];
    }

    public function isSaveNewFlags(): bool
    {
        return $this->saveNewFlags;
    }

    public function getDefaultAmount(): int
    {
        return $this->defaultAmount;
    }

    public function getMinRegionNameLength(): int
    {
        return $this->minRegionNameLength;
    }

    public function getMaxRegionNameLength(): int
    {
        return $this->maxRegionNameLength;
    }

    public function getHealFlagHealDelay(): int
    {
        return $this->healFlagHealDelay;
    }

    public function getHealFlagHealAmount(): float
    {
        return $this->healFlagHealAmount;
    }

    public function getDefaultSize(): int
    {
        return $this->defaultSize;
    }

    public function needMessage(int $flag): bool
    {
        return $this->needMessage[$flag];
    }

    public function getDefaultFlags(): array
    {
        return $this->defaultFlags;
    }

    public function getDisplay(): array
    {
        return $this->display;
    }

    public function getFlagStatus(): array
    {
        return $this->flagStatus;
    }

    public function getNeedMessage(): array
    {
        return $this->needMessage;
    }
}
