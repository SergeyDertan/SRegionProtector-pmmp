<?php
declare(strict_types=1);

namespace sergeydertan\sregionprotector\ui\form\type;

use pocketmine\Player;
use sergeydertan\sregionprotector\region\Region;

interface UIForm
{
    const MAIN = MainForm::class;

    const FORM_ID_MAIN = 1200;
    const FORM_ID_FLAGS = 1201;
    const FORM_ID_MEMBERS = 1202;
    const FORM_ID_REMOVE_MEMBER = 1203;

    const FORM_ID_OWNERS = 1204;
    const FORM_ID_REMOVE_OWNER = 1205;

    const FORM_ID_REMOVE_REGION = 1206;

    const FORM_ID_SELL_REGION = 1207;

    const FORM_ID_SET_PRIORITY = 1208;

    public function getRegion(): Region;

    public function handle($response, Player $player): ?UIForm;
}
