<?php
declare(strict_types=1);

namespace sergeydertan\sregionprotector\ui\form\type;

use pocketmine\Player;
use sergeydertan\sregionprotector\region\flags\RegionFlags;
use sergeydertan\sregionprotector\region\Region;
use sergeydertan\sregionprotector\ui\form\element\Button;
use sergeydertan\sregionprotector\util\form\element\ElementButtonImageData;
use sergeydertan\sregionprotector\util\form\FormWindowSimple;

class FlagsForm extends FormWindowSimple implements UIForm
{
    private static $icons = [];

    /**
     * @var bool[]
     */
    private static $display;
    /**
     * @var bool[]
     */
    private static $flagStatus;
    /**
     * @var Region
     */
    private $region;

    public function __construct(Region $region, Player $player)
    {
        parent::__construct("{$region->getName()}`s flags");

        $this->region = $region;

        $i = 0;
        foreach ($region->getFlags() as $flag) {
            $str = str_replace("-", " ", RegionFlags::getFlagName($i)) . ": ";
            $str .= ($flag->state === RegionFlags::getStateFromString("allow", $i) ? "allow" : "deny");

            $str[0] = strtoupper($str{0});

            $beforeNext = null;

            if (!self::$display[$i] || !self::$flagStatus[$i]) {
                ++$i;
                continue;
            }
            if ($i !== RegionFlags::FLAG_TELEPORT && $i !== RegionFlags::FLAG_SELL) {
                $beforeNext = function () use ($i, $player, $region): void {
                    if (RegionFlags::hasFlagPermission($player, $i)) $region->setFlagState($i, !$region->getFlagState($i));
                };
            }

            $image = new ElementButtonImageData(ElementButtonImageData::IMAGE_TYPE_PATH, self::$icons[$i]);
            $this->buttons[] = (new Button($str, FlagsForm::class, $region, $player))->setBeforeNext($beforeNext)->setImage($image);
            ++$i;
        }
    }

    public static function initFlags(array $display, array $flagStatus): void
    {
        self::$display = $display;
        self::$flagStatus = $flagStatus;
    }

    public static function initIcons(): void
    {
        self::$icons = array_fill(0, RegionFlags::FLAG_AMOUNT, "textures/misc/missing_texture.png");

        self::$icons[RegionFlags::FLAG_PLACE] = "textures/blocks/grass_side_carried.png";
        self::$icons[RegionFlags::FLAG_BREAK] = "textures/blocks/grass_side_carried.png";
        self::$icons[RegionFlags::FLAG_USE] = "textures/items/lever.png";
        self::$icons[RegionFlags::FLAG_PVP] = "textures/items/diamond_sword.png";
        self::$icons[RegionFlags::FLAG_EXPLODE] = "textures/blocks/tnt_side.png";
        self::$icons[RegionFlags::FLAG_EXPLODE_BLOCK_BREAK] = "textures/blocks/tnt_side.png";
        self::$icons[RegionFlags::FLAG_LIGHTER] = "textures/items/flint_and_steel.png";
        self::$icons[RegionFlags::FLAG_LEAVES_DECAY] = "textures/blocks/leaves_acacia_carried.tga";
        self::$icons[RegionFlags::FLAG_ITEM_DROP] = "textures/items/stick.png";
        self::$icons[RegionFlags::FLAG_MOB_SPAWN] = "textures/items/egg_creeper.png";
        self::$icons[RegionFlags::FLAG_CROPS_DESTROY] = "textures/blocks/farmland_wet.png";
        self::$icons[RegionFlags::FLAG_REDSTONE] = "textures/items/redstone_dust.png";
        self::$icons[RegionFlags::FLAG_ENDER_PEARL] = "textures/items/ender_pearl.png";
        self::$icons[RegionFlags::FLAG_FIRE] = "textures/blocks/fire_1_placeholder.png";
        self::$icons[RegionFlags::FLAG_LIQUID_FLOW] = "textures/blocks/water_placeholder.png";
        self::$icons[RegionFlags::FLAG_CHEST_ACCESS] = "textures/blocks/chest_front.png";
        self::$icons[RegionFlags::FLAG_SLEEP] = "textures/items/bed_red.png";
        self::$icons[RegionFlags::FLAG_SMART_DOORS] = "textures/items/door_iron.png";
        self::$icons[RegionFlags::FLAG_MINEFARM] = "textures/blocks/redstone_ore.png";
        self::$icons[RegionFlags::FLAG_POTION_LAUNCH] = "textures/items/potion_bottle_splash_healthBoost.png";
        self::$icons[RegionFlags::FLAG_HEAL] = "textures/ui/regeneration_effect.png";
        self::$icons[RegionFlags::FLAG_HEALTH_REGEN] = "textures/ui/regeneration_effect.png";
        self::$icons[RegionFlags::FLAG_NETHER_PORTAL] = "textures/ui/NetherPortal.png";
        self::$icons[RegionFlags::FLAG_SEND_CHAT] = "textures/ui/chat_send.png";
        self::$icons[RegionFlags::FLAG_RECEIVE_CHAT] = "textures/ui/betaIcon.png";
        self::$icons[RegionFlags::FLAG_FRAME_ITEM_DROP] = "textures/items/item_frame.png";
        self::$icons[RegionFlags::FLAG_BUCKET_EMPTY] = "textures/items/bucket_empty.png";
        self::$icons[RegionFlags::FLAG_BUCKET_FILL] = "textures/items/bucket_water.png";
        self::$icons[RegionFlags::FLAG_INVINCIBLE] = "textures/ui/fire_resistance_effect.png";
    }

    public function getRegion(): Region
    {
        return $this->region;
    }

    public function getId(): int
    {
        return self::FORM_ID_FLAGS;
    }

    public function handle($response, Player $player): ?UIForm
    {
        return $this;
    }
}
