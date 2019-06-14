<?php
declare(strict_types=1);

namespace sergeydertan\sregionprotector\ui\form;

use Exception;
use pocketmine\Player;
use ReflectionClass;
use sergeydertan\sregionprotector\event\UIEventsHandler;
use sergeydertan\sregionprotector\region\Region;
use sergeydertan\sregionprotector\region\RegionManager;
use sergeydertan\sregionprotector\settings\RegionSettings;
use sergeydertan\sregionprotector\ui\form\element\Button;
use sergeydertan\sregionprotector\ui\form\type\FlagsForm;
use sergeydertan\sregionprotector\ui\form\type\MainForm;
use sergeydertan\sregionprotector\ui\form\type\MemberRemoveForm;
use sergeydertan\sregionprotector\ui\form\type\OwnerRemoveForm;
use sergeydertan\sregionprotector\ui\form\type\RemoveRegionForm;
use sergeydertan\sregionprotector\ui\form\type\UIForm;
use sergeydertan\sregionprotector\util\form\FormWindow;
use sergeydertan\sregionprotector\util\form\response\FormResponseSimple;

abstract class FormUIManager
{
    /**
     * @var RegionManager
     */
    private static $regionManager;
    /**
     * @var UIEventsHandler
     */
    private static $uiHandler;

    private function __construct()
    {
    }

    public static function init(RegionSettings $settings, RegionManager $regionManager, UIEventsHandler $handler): void
    {
        self::$regionManager = $regionManager;
        self::$uiHandler = $handler;

        MemberRemoveForm::setRegionManager($regionManager);
        OwnerRemoveForm::setRegionManager($regionManager);
        RemoveRegionForm::setRegionManager($regionManager);

        FlagsForm::initIcons();
        FlagsForm::initFlags($settings->getDisplay(), $settings->getFlagStatus());
    }

    public static function handle(FormWindow $form, Player $player): void
    {
        /**
         * @var UIForm $form
         */
        if (!self::$regionManager->regionExists($form->getRegion()->getName())) return;
        $response = $form->getResponse();
        if ($response === null) return;
        if ($response instanceof FormResponseSimple) {
            $btn = $response->getClickedButton();
            if ($btn instanceof Button) {
                $next = $btn->getNext();
                if ($next !== null) self::$uiHandler->sendForm($next, $player);
                return;
            }
        }
        $next = $form->handle($form->getResponse(), $player);
        if ($next !== null) self::$uiHandler->sendForm($next, $player);
    }

    public static function open(Player $player, Region $region): void
    {
        self::$uiHandler->sendForm(self::getPageInstance(MainForm::class, $region, $player), $player);
    }

    public static function getPageInstance(string $class, ...$args): ?UIForm
    {
        try {
            return (new ReflectionClass($class))->newInstance(...$args);
        } catch (Exception$e) {
            return null;
        }
    }
}
