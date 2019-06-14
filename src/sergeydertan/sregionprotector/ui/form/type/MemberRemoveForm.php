<?php
declare(strict_types=1);

namespace sergeydertan\sregionprotector\ui\form\type;

use pocketmine\Player;
use sergeydertan\sregionprotector\region\Region;
use sergeydertan\sregionprotector\region\RegionManager;
use sergeydertan\sregionprotector\ui\form\element\Button;
use sergeydertan\sregionprotector\util\form\FormWindowSimple;

class MemberRemoveForm extends FormWindowSimple implements UIForm
{
    /**
     * @var RegionManager
     */
    private static $regionManager;
    /**
     * @var Region
     */
    private $region;

    public function __construct(string $owner, Region $region, Player $player)
    {
        parent::__construct($region->getName(), "Do u want to remove member $owner from {$region->getName()}?");

        $this->region = $region;

        $regionManager = self::$regionManager;
        $this->buttons[] = (new Button("Yes", MembersForm::class, $region, $player))->setBeforeNext(function () use ($region, $owner, $regionManager): void {
            if ($region->isMember($owner)) $regionManager->removeMember($region, $owner);
        });
        $this->buttons[] = new Button("No", MembersForm::class, $region, $player);
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
        return self::FORM_ID_REMOVE_MEMBER;
    }
}
