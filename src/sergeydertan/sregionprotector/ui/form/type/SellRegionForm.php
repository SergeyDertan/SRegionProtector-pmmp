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

class SellRegionForm extends FormWindowCustom implements UIForm
{
    /**
     * @var Region
     */
    private $region;

    public function __construct(Region $region, string $err = "")
    {
        parent::__construct("Sell region {$region->getName()}");
        $this->region = $region;

        $this->content[] = new ElementLabel($err);
        $this->content[] = new ElementInput("Price", "PRICE");
    }

    public function getRegion(): Region
    {
        return $this->region;
    }

    public function getId(): int
    {
        return self::FORM_ID_SELL_REGION;
    }

    public function handle($response, Player $player): ?UIForm
    {
        if (!$player->hasPermission("sregionprotector.admin") && !$this->region->isCreator($player->getName())) return null;
        /**
         * @var FormResponseCustom $response
         */
        if (!is_numeric($response->inputResponses[1])) {
            return FormUIManager::getPageInstance(self::class, $this->region, "Wrong price!");
        }
        $price = (int)$response->inputResponses[1];
        if ($price <= 0) {
            return FormUIManager::getPageInstance(self::class, $this->region, "Wrong price!");
        }
        $this->region->setSellFlag($price, true);
        return FormUIManager::getPageInstance(self::class, $this->region, "Success! Current price: $price");
    }
}
