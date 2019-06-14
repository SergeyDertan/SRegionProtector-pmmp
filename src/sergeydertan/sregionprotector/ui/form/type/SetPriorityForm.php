<?php
declare(strict_types=1);

namespace sergeydertan\sregionprotector\ui\form\type;

use pocketmine\Player;
use sergeydertan\sregionprotector\region\Region;
use sergeydertan\sregionprotector\ui\form\FormUIManager;
use sergeydertan\sregionprotector\util\form\element\ElementInput;
use sergeydertan\sregionprotector\util\form\element\ElementLabel;
use sergeydertan\sregionprotector\util\form\FormWindowCustom;
use sergeydertan\sregionprotector\util\form\response\FormResponseCustom;

class SetPriorityForm extends FormWindowCustom implements UIForm
{
    /**
     * @var Region
     */
    private $region;

    public function __construct(Region $region, string $err = "")
    {
        parent::__construct("Changing priority for {$region->getName()}");
        $this->region = $region;

        $this->content[] = new ElementLabel("Current priority: {$region->getPriority()}");
        $this->content[] = new ElementLabel($err);
        $this->content[] = new ElementInput("Priority", "PRIORITY");
    }

    public function getRegion(): Region
    {
        return $this->region;
    }

    public function getId(): int
    {
        return self::FORM_ID_SET_PRIORITY;
    }

    public function handle($response, Player $player): ?UIForm
    {
        if (!$player->hasPermission("sregionprotector.admin") && !$this->region->isCreator($player->getName())) return null;
        /**
         * @var FormResponseCustom $response
         */
        if (!is_numeric($response->inputResponses[2])) {
            return FormUIManager::getPageInstance(self::class, $this->region, "Wrong priority!");
        }
        $this->region->setPriority((int)$response->inputResponses[2]);
        return FormUIManager::getPageInstance(self::class, $this->region);
    }
}
