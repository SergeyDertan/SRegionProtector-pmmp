<?php
declare(strict_types=1);

namespace sergeydertan\sregionprotector\ui\form\type;

use pocketmine\Player;
use sergeydertan\sregionprotector\region\Region;
use sergeydertan\sregionprotector\ui\form\element\Button;
use sergeydertan\sregionprotector\util\form\FormWindowSimple;

class MembersForm extends FormWindowSimple implements UIForm
{
    /**
     * @var Region
     */
    private $region;

    public function __construct(Region $region, Player $player)
    {
        parent::__construct("{$region->getName()}`s members");

        $this->region = $region;

        if ($player->hasPermission("sregionprotector.admin") || $region->isOwner($player->getName(), true)) {
            foreach ($region->getMembers() as $member) {
                $this->buttons[] = new Button($member, MemberRemoveForm::class, $member, $region, $player);
            }
        } else {
            foreach ($region->getMembers() as $member) {
                $this->buttons[] = new Button($member, self::class, $region, $player);
            }
        }

        $this->buttons[] = new Button("Back", MainForm::class, $region, $player);
    }

    public function getRegion(): Region
    {
        return $this->region;
    }

    public function getId(): int
    {
        return self::FORM_ID_MEMBERS;
    }

    public function handle($response, Player $player): ?UIForm
    {
        return $this;
    }
}
