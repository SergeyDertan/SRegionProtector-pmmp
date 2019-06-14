<?php
declare(strict_types=1);

namespace sergeydertan\sregionprotector\ui\form\type;

use pocketmine\Player;
use sergeydertan\sregionprotector\region\Region;
use sergeydertan\sregionprotector\ui\form\element\Button;
use sergeydertan\sregionprotector\util\form\element\ElementButtonImageData;
use sergeydertan\sregionprotector\util\form\FormWindowSimple;

class MainForm extends FormWindowSimple implements UIForm
{
    private const PRIORITY_IMAGE = "textures/ui/move.png";
    private const MEMBERS_IMAGE = "textures/ui/permissions_member_star.png";
    private const REMOVE_IMAGE = "textures/gui/newgui/storage/trash.png";
    private const OWNERS_IMAGE = "textures/ui/permissions_op_crown.png";
    private const FLAGS_IMAGE = "textures/gui/newgui/settings/toggle_on_hover.png";
    private const SELL_IMAGE = "textures/gui/newgui/MCoin.png";
    /**
     * @var Region
     */
    private $region;

    public function __construct(Region $region, Player $player)
    {
        parent::__construct("Region '{$region->getName()}'",
            "Level: {$region->getLevel()}\n" .
            "Creator: {$region->getCreator()}\n" .
            "Priority: {$region->getPriority()}\n" .
            "Size: {$region->getSize()}");
        $this->region = $region;

        $this->buttons[] = (new Button("Owners", OwnersForm::class, $region, $player))->setImage(new ElementButtonImageData(ElementButtonImageData::IMAGE_TYPE_PATH, self::OWNERS_IMAGE));
        $this->buttons[] = (new Button("Members", MembersForm::class, $region, $player))->setImage(new ElementButtonImageData(ElementButtonImageData::IMAGE_TYPE_PATH, self::MEMBERS_IMAGE));
        $this->buttons[] = (new Button("Flags", FlagsForm::class, $region, $player))->setImage(new ElementButtonImageData(ElementButtonImageData::IMAGE_TYPE_PATH, self::FLAGS_IMAGE));
        if ($player->hasPermission("sregionprotector.admin") || $region->isCreator($player->getName())) {
            $this->buttons[] = (new Button("Sell region", SellRegionForm::class, $region))->setImage(new ElementButtonImageData(ElementButtonImageData::IMAGE_TYPE_PATH, self::SELL_IMAGE));
            $this->buttons[] = (new Button("Set priority", SetPriorityForm::class, $region, ""))->setImage(new ElementButtonImageData(ElementButtonImageData::IMAGE_TYPE_PATH, self::PRIORITY_IMAGE));
            $this->buttons[] = (new Button("Remove region", RemoveRegionForm::class, $region, $player))->setImage(new ElementButtonImageData(ElementButtonImageData::IMAGE_TYPE_PATH, self::REMOVE_IMAGE));
        }
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
        return self::FORM_ID_MAIN;
    }
}
