<?php
declare(strict_types=1);

namespace sergeydertan\sregionprotector\main;

use Exception;
use pocketmine\command\Command;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\Task;
use pocketmine\Server;
use pocketmine\tile\Tile;
use pocketmine\utils\TextFormat;
use RuntimeException;
use sergeydertan\sregionprotector\blockentity\BlockEntityHealer;
use sergeydertan\sregionprotector\command\creation\CreateRegionCommand;
use sergeydertan\sregionprotector\command\creation\GetWandCommand;
use sergeydertan\sregionprotector\command\creation\LPos1Command;
use sergeydertan\sregionprotector\command\creation\LPos2Command;
use sergeydertan\sregionprotector\command\creation\Pos1Command;
use sergeydertan\sregionprotector\command\creation\Pos2Command;
use sergeydertan\sregionprotector\command\creation\RegionExpandCommand;
use sergeydertan\sregionprotector\command\creation\RegionSizeCommand;
use sergeydertan\sregionprotector\command\creation\ShowBorderCommand;
use sergeydertan\sregionprotector\command\manage\OpenUICommand;
use sergeydertan\sregionprotector\command\RegionCommand;
use sergeydertan\sregionprotector\event\RegionEventsHandler;
use sergeydertan\sregionprotector\event\SelectorEventsHandler;
use sergeydertan\sregionprotector\event\UIEventsHandler;
use sergeydertan\sregionprotector\messenger\Messenger;
use sergeydertan\sregionprotector\provider\DataProvider;
use sergeydertan\sregionprotector\provider\YAMLDataProvider;
use sergeydertan\sregionprotector\region\chunk\ChunkManager;
use sergeydertan\sregionprotector\region\RegionManager;
use sergeydertan\sregionprotector\region\selector\RegionSelector;
use sergeydertan\sregionprotector\settings\Settings;
use sergeydertan\sregionprotector\ui\chest\page\Page;
use sergeydertan\sregionprotector\util\Utils;

final class SRegionProtectorMain extends PluginBase
{
    private const VERSION_URL = "";
    /**
     * @var SRegionProtectorMain
     */
    private static $INSTANCE;
    /**
     * @var string
     */
    private $mainFolder, $regionsFolder, $langFolder, $dbFolder;
    /**
     * @var Settings
     */
    private $settings;
    /**
     * @var DataProvider
     */
    private $dataProvider;
    /**
     * @var RegionManager
     */
    private $regionManager;
    /**
     * @var ChunkManager
     */
    private $chunkManager;
    /**
     * @var RegionSelector
     */
    private $regionSelector;
    /**
     * @var Messenger
     */
    private $messenger;
    /**
     * @var RegionCommand
     */
    private $mainCommand;

    public function onEnable(): void
    {
        self::$INSTANCE = $this;

        $start = Utils::currentTimeMillis();

        if (!$this->createDirectories()) return;
        $this->initMessenger();

        $this->getLogger()->info(TextFormat::GREEN . $this->messenger->getMessage("loading.init.start", ["@ver"], [$this->getDescription()->getVersion()]));

        //TODO remove lib msg

        $this->getLogger()->info(TextFormat::GREEN . $this->messenger->getMessage("loading.init.settings"));
        $this->initSettings();

        $this->getLogger()->info(TextFormat::GREEN . $this->messenger->getMessage("loading.init.data-provider"));
        if (!$this->initDataProvider()) return;

        $this->initChunks();

        $this->getLogger()->info(TextFormat::GREEN . $this->messenger->getMessage("loading.init.regions"));
        $this->initRegions();

        $this->getLogger()->info(TextFormat::GREEN . $this->messenger->getMessage("loading.init.events-handlers"));
        $this->initEventsHandlers();

        $this->getLogger()->info(TextFormat::GREEN . $this->messenger->getMessage("loading.init.commands"));
        $this->initCommands();

        $this->registerBlockEntity();

        $this->initAutoSave();

        $this->initSessionsClearTask();

        $this->initUI();

        $this->getLogger()->info(TextFormat::GREEN . $this->messenger->getMessage("loading.init.successful", ["@time"], [(string)(Utils::currentTimeMillis() - $start)]));

        //TODO check update
    }

    private function createDirectories(): bool
    {
        $this->mainFolder = Server::getInstance()->getDataPath() . "Sergey_Dertan_Plugins/";
        $this->regionsFolder = $this->mainFolder . "regions/";
        $this->langFolder = $this->mainFolder . "lang/";
        $this->dbFolder = $this->mainFolder . "db/";

        return
            Utils::createDir($this->mainFolder) &&
            Utils::createDir($this->regionsFolder) &&
            Utils::createDir($this->langFolder) &&
            Utils::createDir($this->dbFolder);
    }

    private function initMessenger(): void
    {
        $this->messenger = new Messenger();
    }

    private function initSettings(): void
    {
        $this->settings = new Settings();
    }

    private function initDataProvider(): bool
    {
        try {
            $this->dataProvider = $this->getProviderInstance($this->settings->getProvider());
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function getProviderInstance(int $type): DataProvider
    {
        if ($this->dataProvider !== null && $this->dataProvider->getType() === $type) return $this->dataProvider;
        switch ($type) {
            default:
                throw new RuntimeException("Unknown provider");
                break;
            case DataProvider::YAML:
                return new YAMLDataProvider();
        }
    }

    private function initChunks(): void
    {
        $this->chunkManager = new ChunkManager($this->getLogger());
        $this->chunkManager->init($this->settings->isEmptyChunksRemoving(), $this->settings->getEmptyChunksRemovingPeriod(), $this->getScheduler());
    }

    private function initRegions(): void
    {
        $this->regionSelector = new RegionSelector($this->settings->getSelectorSessionLifetime(), $this->settings->getBorderBlock());
        $this->regionManager = new RegionManager($this->dataProvider, $this->getLogger(), $this->chunkManager);
        $this->regionManager->init($this->settings->getRegionSettings()->isSaveNewFlags());
    }

    private function initEventsHandlers(): void
    {
        $this->getServer()->getPluginManager()->registerEvents(new RegionEventsHandler(
            $this->chunkManager,
            $this->settings->getRegionSettings()->getFlagStatus(),
            $this->settings->getRegionSettings()->getNeedMessage(),
            $this->settings->isPrioritySystem(),
            $this->settings->getProtectedMessageType(),
            $this->settings->isShowParticle()
        ), $this);

        $this->getServer()->getPluginManager()->registerEvents(new SelectorEventsHandler($this->regionSelector), $this);

        $this->getServer()->getPluginManager()->registerEvents(new UIEventsHandler($this->settings->getUiType()), $this);
    }

    private function initCommands(): void
    {
        $this->mainCommand = new RegionCommand();
        $this->getServer()->getCommandMap()->register($this->mainCommand->getName(), $this->mainCommand);

        $this->registerCommand(new CreateRegionCommand($this->regionSelector, $this->regionManager, $this->settings->getRegionSettings()));

        $this->registerCommand(new GetWandCommand());

        $this->registerCommand(new LPos1Command($this->regionSelector, $this->settings->getLposMaxRadius()));

        $this->registerCommand(new LPos2Command($this->regionSelector, $this->settings->getLposMaxRadius()));

        $this->registerCommand(new Pos1Command($this->regionSelector));

        $this->registerCommand(new Pos2Command($this->regionSelector));

        $this->registerCommand(new RegionSizeCommand($this->regionSelector));

        $this->registerCommand(new RegionExpandCommand($this->regionSelector));

        $this->registerCommand(new ShowBorderCommand($this->regionSelector, $this->settings->getMaxBordersAmount()));

        $this->registerCommand(new OpenUICommand($this->regionManager, $this->chunkManager, $this->settings->getUiType()));
    }

    private function registerCommand(Command $command): void
    {
        if (!$this->settings->isHideCommands()) $this->getServer()->getCommandMap()->register($command->getName(), $command);
        $this->mainCommand->registerCommand($command);
    }

    private function registerBlockEntity(): void
    {
        Tile::registerTile(BlockEntityHealer::class, [BlockEntityHealer::BLOCK_ENTITY_HEALER]);
    }

    private function initAutoSave(): void
    {
        if (!$this->settings->isAutoSave()) return;
        $this->getScheduler()->scheduleDelayedRepeatingTask(new class extends Task
        {
            public function onRun(int $tick): void
            {
                SRegionProtectorMain::getInstance()->save(SaveType::AUTO);
            }
        }, $this->settings->getAutoSavePeriod(), $this->settings->getAutoSavePeriod());
    }

    public function save(int $type, string $initiator = null): void
    {
        switch ($type) {
            default:
            case SaveType::AUTO:
                $this->getLogger()->info(TextFormat::GREEN . $this->messenger->getMessage("auto-save-start"));
                break;
            case SaveType::MANUAL:
                $this->getLogger()->info(TextFormat::GREEN . $this->messenger->getMessage("manual-save-start"));
                break;
            case SaveType::DISABLING:
                $this->getLogger()->info(TextFormat::GREEN . $this->messenger->getMessage("disabling-save-start"));
                break;
        }
        $this->regionManager->save($type, $initiator);
    }

    /**
     * @return SRegionProtectorMain
     */
    public static function getInstance(): ?SRegionProtectorMain
    {
        return self::$INSTANCE;
    }

    private function initSessionsClearTask(): void
    {
        $this->getScheduler()->scheduleDelayedRepeatingTask(new class extends Task
        {
            public function onRun(int $tick): void
            {
                SRegionProtectorMain::getInstance()->getRegionSelector()->clear();
            }
        }, $this->settings->getSelectorSessionClearInterval(), $this->settings->getSelectorSessionClearInterval());
    }

    public function getRegionSelector(): RegionSelector
    {
        return $this->regionSelector;
    }

    private function initUI(): void
    {
        Page::initDefaultPages();
    }

    public function getRegionsFolder(): string
    {
        return $this->regionsFolder;
    }

    public function getMainFolder(): string
    {
        return $this->mainFolder;
    }

    public function getLangFolder(): string
    {
        return $this->langFolder;
    }

    public function isPhar(): bool
    {
        return parent::isPhar();
    }

    public function getFile(): string
    {
        return parent::getFile();
    }

    public function getRegionManager(): RegionManager
    {
        return $this->regionManager;
    }

    public function getChunkManager(): ChunkManager
    {
        return $this->chunkManager;
    }

    public function getSettings(): Settings
    {
        return $this->settings;
    }

    public function getMessenger(): Messenger
    {
        return $this->messenger;
    }

    public function getMainCommand(): RegionCommand
    {
        return $this->mainCommand;
    }

    public function getDbFolder(): string
    {
        return $this->dbFolder;
    }

    public function getDataProvider(): DataProvider
    {
        return $this->dataProvider;
    }
}
