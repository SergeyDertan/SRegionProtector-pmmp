<?php
declare(strict_types=1);

namespace sergeydertan\sregionprotector\settings;

use pocketmine\block\Block;
use pocketmine\utils\Config;
use sergeydertan\sregionprotector\main\SRegionProtectorMain;
use sergeydertan\sregionprotector\messenger\Messenger;
use sergeydertan\sregionprotector\provider\DataProvider;
use sergeydertan\sregionprotector\ui\UIType;
use sergeydertan\sregionprotector\util\Utils;

final class Settings
{
    /**
     * @var int
     */
    private $autoSavePeriod;
    /**
     * @var bool
     */
    private $autoSave;

    /**
     * @var Block
     */
    private $borderBlock;

    /**
     * @var bool
     */
    private $hideCommands;

    /**
     * @var int
     */
    private $provider;

    /**
     * @var bool
     */
    private $emptyChunksRemoving;
    /**
     * @var int
     */
    private $emptyChunksRemovingPeriod;

    /**
     * @var int
     */
    private $lposMaxRadius;

    /**
     * @var bool
     */
    private $prioritySystem;

    /**
     * @var bool
     */
    private $updateNotifier;

    /**
     * @var int
     */
    private $uiType;

    /**
     * @var int
     */
    private $selectorSessionClearInterval;
    /**
     * @var int
     */
    private $selectorSessionLifetime;

    /**
     * @var int
     */
    private $maxBordersAmount;

    /**
     * @var int
     */
    private $protectedMessageType;

    /**
     * @var bool
     */
    private $showParticle;
    /**
     * @var RegionSettings
     */
    private $regionSettings;

    public function __construct()
    {
        Utils::copyResource("config.yml");
        //Utils::copyResource("db/mysql.yml");
        //Utils::copyResource("db/postgresql.yml");
        //Utils::copyResource("db/sqlite.yml");
        Utils::copyResource("region-settings.yml");

        $cnf = $this->getConfig();

        $this->selectorSessionLifetime = (int)$cnf["session-life-time"] * 1000;
        $this->selectorSessionClearInterval = (int)$cnf["select-session-clear-interval"] * 20;

        $this->autoSavePeriod = (int)$cnf["auto-save-period"] * 20;
        $this->autoSave = (bool)$cnf["auto-save"];

        $this->emptyChunksRemovingPeriod = (int)$cnf["empty-chunks-removing-period"];
        $this->emptyChunksRemoving = (bool)$cnf["empty-chunks-auto-removing"];

        $this->hideCommands = (bool)$cnf["hide-commands"];

        $this->lposMaxRadius = (int)$cnf["lpos-max-radius"];

        $this->prioritySystem = (bool)$cnf["priority-system"];

        $this->updateNotifier = (bool)$cnf["update-notifier"];

        $this->showParticle = (bool)$cnf["show-particle"];

        $this->maxBordersAmount = (int)$cnf["max-borders-amount"];

        $this->uiType = UIType::typeFromString((string)$cnf["ui-type"]);

        $this->protectedMessageType = Messenger::messageTypeFromString((string)$cnf["protected-message-type"]);

        $border = (string)$cnf["border-block"];
        $meta = 0;
        if (count(explode(":", $border)) === 2) {
            $id = (int)explode(":", $border)[0];
            $meta = (int)explode(":", $border)[1];
        } else {
            $id = (int)$border;
        }
        $this->borderBlock = Block::get($id, $meta);

        $this->provider = DataProvider::providerFromString((string)$cnf["provider"]);

        $this->regionSettings = new RegionSettings($cnf, (new Config(SRegionProtectorMain::getInstance()->getMainFolder() . "region-settings.yml", Config::YAML))->getAll());
    }

    public function getConfig(): array
    {
        return (new Config(SRegionProtectorMain::getInstance()->getMainFolder() . "config.yml"))->getAll();
    }

    public function getAutoSavePeriod(): int
    {
        return $this->autoSavePeriod;
    }

    public function isPrioritySystem(): bool
    {
        return $this->prioritySystem;
    }

    public function isShowParticle(): bool
    {
        return $this->showParticle;
    }

    public function isHideCommands(): bool
    {
        return $this->hideCommands;
    }

    public function isUpdateNotifier(): bool
    {
        return $this->updateNotifier;
    }

    public function getBorderBlock(): Block
    {
        return $this->borderBlock;
    }

    public function isEmptyChunksRemoving(): bool
    {
        return $this->emptyChunksRemoving;
    }

    public function getEmptyChunksRemovingPeriod(): int
    {
        return $this->emptyChunksRemovingPeriod;
    }

    public function getLposMaxRadius(): int
    {
        return $this->lposMaxRadius;
    }

    public function getMaxBordersAmount(): int
    {
        return $this->maxBordersAmount;
    }

    public function getProtectedMessageType(): int
    {
        return $this->protectedMessageType;
    }

    public function getProvider(): int
    {
        return $this->provider;
    }

    public function getRegionSettings(): RegionSettings
    {
        return $this->regionSettings;
    }

    public function isAutoSave(): bool
    {
        return $this->autoSave;
    }

    public function getSelectorSessionClearInterval(): int
    {
        return $this->selectorSessionClearInterval;
    }

    public function getSelectorSessionLifetime(): int
    {
        return $this->selectorSessionLifetime;
    }

    public function getUiType(): int
    {
        return $this->uiType;
    }
}
