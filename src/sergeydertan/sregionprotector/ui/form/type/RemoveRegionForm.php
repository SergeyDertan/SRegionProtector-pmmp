<?php
declare(strict_types=1);

namespace sergeydertan\sregionprotector\ui\form\type;

use pocketmine\Player;
use sergeydertan\sregionprotector\region\Region;
use sergeydertan\sregionprotector\region\RegionManager;
use sergeydertan\sregionprotector\ui\form\element\Button;
use sergeydertan\sregionprotector\util\form\element\ElementButtonImageData;
use sergeydertan\sregionprotector\util\form\FormWindowSimple;

class RemoveRegionForm extends FormWindowSimple implements UIForm
{
    private const ACCEPT_IMG = "textures/ui/confirm.png";
    private const CANCEL_IMG = "textures/ui/cancel.png";
    /**
     * @var RegionManager
     */
    private static $regionManager;
    /**
     * @var Region
     */
    private $region;

    public function __construct(Region $region, Player $player)
    {
        parent::__construct("Removing {$region->getName()}", "Remove region {$region->getName()}?");
        $this->region = $region;

        $regionManager = self::$regionManager;
        $this->buttons[] = (new Button("Yes", ""))->setBeforeNext(function () use ($regionManager, $region, $player): void {
            if ($regionManager->regionExists($region->getName()) && ($player->hasPermission("sregionprotector.admin") || $region->isCreator($player->getName()))) {
                $regionManager->removeRegion($region);
                $player->sendMessage("Region {$region->getName()} removed");
            }
        })->noNext(true)->setImage(new ElementButtonImageData(ElementButtonImageData::IMAGE_TYPE_PATH, self::ACCEPT_IMG));

        $this->buttons[] = (new Button("No", self::MAIN, $region, $player))->setImage(new ElementButtonImageData(ElementButtonImageData::IMAGE_TYPE_PATH, self::CANCEL_IMG));
    }

    public static function setRegionManager(RegionManager $regionManager): void
    {
        self::$regionManager = $regionManager;
    }

    public function getRegion(): Region
    {
        return $this->region;
    }

    public function handle($response, Player $player): ?UIForm
    {
        return $this;
    }

    public function getId(): int
    {
        return self::FORM_ID_REMOVE_REGION;
    }
}
