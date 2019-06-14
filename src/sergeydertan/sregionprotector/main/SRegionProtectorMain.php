<?php
declare(strict_types=1);

namespace sergeydertan\sregionprotector\main;

use Exception;
use pocketmine\command\Command;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\tile\Tile;
use pocketmine\utils\TextFormat;
use RuntimeException;
use sergeydertan\sregionprotector\blockentity\BlockEntityHealer;
use sergeydertan\sregionprotector\command\admin\SaveCommand;
use sergeydertan\sregionprotector\command\creation\CreateRegionCommand;
use sergeydertan\sregionprotector\command\creation\GetWandCommand;
use sergeydertan\sregionprotector\command\creation\LPos1Command;
use sergeydertan\sregionprotector\command\creation\LPos2Command;
use sergeydertan\sregionprotector\command\creation\Pos1Command;
use sergeydertan\sregionprotector\command\creation\Pos2Command;
use sergeydertan\sregionprotector\command\creation\RegionExpandCommand;
use sergeydertan\sregionprotector\command\creation\RegionSizeCommand;
use sergeydertan\sregionprotector\command\creation\ShowBorderCommand;
use sergeydertan\sregionprotector\command\manage\CopyFlagsCommand;
use sergeydertan\sregionprotector\command\manage\group\AddMemberCommand;
use sergeydertan\sregionprotector\command\manage\group\AddOwnerCommand;
use sergeydertan\sregionprotector\command\manage\group\RemoveMemberCommand;
use sergeydertan\sregionprotector\command\manage\group\RemoveOwnerCommand;
use sergeydertan\sregionprotector\command\manage\OpenUICommand;
use sergeydertan\sregionprotector\command\manage\purchase\BuyRegionCommand;
use sergeydertan\sregionprotector\command\manage\purchase\RegionPriceCommand;
use sergeydertan\sregionprotector\command\manage\purchase\RegionRemoveFromSaleCommand;
use sergeydertan\sregionprotector\command\manage\purchase\RegionSellCommand;
use sergeydertan\sregionprotector\command\manage\RegionFlagCommand;
use sergeydertan\sregionprotector\command\manage\RegionInfoCommand;
use sergeydertan\sregionprotector\command\manage\RegionListCommand;
use sergeydertan\sregionprotector\command\manage\RegionRemoveCommand;
use sergeydertan\sregionprotector\command\manage\RegionSelectCommand;
use sergeydertan\sregionprotector\command\manage\RegionTeleportCommand;
use sergeydertan\sregionprotector\command\manage\RemoveBordersCommand;
use sergeydertan\sregionprotector\command\manage\SetPriorityCommand;
use sergeydertan\sregionprotector\command\RegionCommand;
use sergeydertan\sregionprotector\economy\OneBoneEconomyAPI;
use sergeydertan\sregionprotector\event\NotifierEventsHandler;
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
use sergeydertan\sregionprotector\ui\form\FormUIManager;
use sergeydertan\sregionprotector\util\Task;
use sergeydertan\sregionprotector\util\Utils;

final class SRegionProtectorMain extends PluginBase
{
    private const VERSION_URL = "https://api.github.com/repos/SergeyDertan/SRegionProtector-pmmp/releases/latest";
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

        $this->checkUpdate();
    }

    private function createDirectories(): bool
    {
        $this->mainFolder = Server::getInstance()->getDataPath() . "Sergey_Dertan_Plugins/SRegionProtector/";
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

        BlockEntityHealer::setRegionManager($this->regionManager);
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

        $this->getServer()->getPluginManager()->registerEvents($ui = new UIEventsHandler($this->settings->getUiType()), $this);

        FormUIManager::init($this->settings->getRegionSettings(), $this->regionManager, $ui);
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

        $this->registerCommand(new CopyFlagsCommand($this->regionManager));

        $this->registerCommand(new RegionFlagCommand($this->regionManager));

        $this->registerCommand(new RegionInfoCommand($this->regionManager, $this->chunkManager, $this->settings->getRegionSettings()));

        $this->registerCommand(new RegionListCommand($this->regionManager));

        $this->registerCommand(new RegionRemoveCommand($this->regionManager));

        $this->registerCommand(new RegionSelectCommand($this->regionManager, $this->regionSelector, $this->settings->getMaxBordersAmount()));

        $this->registerCommand(new RegionTeleportCommand($this->regionManager));

        $this->registerCommand(new RemoveBordersCommand($this->regionSelector));

        $this->registerCommand(new SetPriorityCommand($this->regionManager, $this->settings->isPrioritySystem()));

        $this->registerCommand(new AddMemberCommand($this->regionManager));

        $this->registerCommand(new AddOwnerCommand($this->regionManager));

        $this->registerCommand(new RemoveMemberCommand($this->regionManager));

        $this->registerCommand(new RemoveOwnerCommand($this->regionManager));

        $economy = $this->getServer()->getPluginManager()->getPlugin("EconomyAPI");
        if ($economy !== null) {
            $economy = new OneBoneEconomyAPI();
        }

        $this->registerCommand(new BuyRegionCommand($this->regionManager, $economy));

        $this->registerCommand(new RegionPriceCommand($this->regionManager));

        $this->registerCommand(new RegionRemoveFromSaleCommand($this->regionManager));

        $this->registerCommand(new RegionSellCommand($this->regionManager));

        $this->registerCommand(new SaveCommand($this));
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
        $this->getScheduler()->scheduleDelayedRepeatingTask(new Task(function (): void {
            $this->save(SaveType::AUTO);
        }), $this->settings->getAutoSavePeriod(), $this->settings->getAutoSavePeriod());
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
        $this->getScheduler()->scheduleDelayedRepeatingTask(new Task(function (): void {
            $this->regionSelector->clear();
        }), $this->settings->getSelectorSessionClearInterval(), $this->settings->getSelectorSessionClearInterval());
    }

    public function getRegionSelector(): RegionSelector
    {
        return $this->regionSelector;
    }

    private function initUI(): void
    {
        Page::init();
    }

    private function checkUpdate(): void
    {
        try {
            $data = Utils::httpRequest(static::VERSION_URL);
            $data = json_decode($data, true);

            $ver = (string)$data["tag_name"];
            $description = (string)$data["name"];

            if (strcasecmp(Utils::compareVersions($ver, $this->getDescription()->getVersion()), $ver) === 0) {
                $this->getLogger()->info(TextFormat::GREEN . $this->messenger->getMessage("loading.init.update-available", ["@ver"], [$ver]));
                $this->getLogger()->info(TextFormat::GREEN . $this->messenger->getMessage("loading.init.update-description", ["@description"], [$description]));

                if ($this->settings->isUpdateNotifier()) {
                    $this->getServer()->getPluginManager()->registerEvents(new NotifierEventsHandler($ver, $description), $this);
                }
            }
        } catch (Exception $ignore) {
        }
    }

    public function onDisable(): void
    {
        $this->getLogger()->info(TextFormat::GREEN . $this->messenger->getMessage("disabling.start", ["@ver"], [$this->getDescription()->getVersion()]));

        $this->save(SaveType::DISABLING);
        $this->dataProvider->close();
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
