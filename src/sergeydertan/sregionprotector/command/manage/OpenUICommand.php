<?php
declare(strict_types=1);

namespace sergeydertan\sregionprotector\command\manage;

use pocketmine\command\CommandSender;
use pocketmine\Player;
use sergeydertan\sregionprotector\command\SRegionProtectorCommand;
use sergeydertan\sregionprotector\region\chunk\ChunkManager;
use sergeydertan\sregionprotector\region\Region;
use sergeydertan\sregionprotector\region\RegionManager;
use sergeydertan\sregionprotector\ui\chest\ChestUIManager;
use sergeydertan\sregionprotector\ui\form\FormUIManager;
use sergeydertan\sregionprotector\ui\UIType;

final class OpenUICommand extends SRegionProtectorCommand
{
    /**
     * @var RegionManager
     */
    private $regionManager;
    /**
     * @var ChunkManager
     */
    private $chunkManager;
    /**
     * @var int
     */
    private $uiType;

    public function __construct(RegionManager $regionManager, ChunkManager $chunkManager, int $uiType)
    {
        parent::__construct("rggui", "gui");

        $this->regionManager = $regionManager;
        $this->uiType = $uiType;
        $this->chunkManager = $chunkManager;
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        if (!$this->checkPerm($sender)) return;

        if (!$this->isPlayer($sender)) return;
        /**
         * @var Player $sender
         */
        if (empty($args)) {
            $region = $this->chunkManager->getRegion($sender, $sender->level->getName());
            if ($region === null) {
                $this->messenger->sendMessage($sender, "command.{$this->msg}.wrong-position");
                return;
            }
            $this->openUI($sender, $region);
        } else {
            $region = $this->regionManager->getRegion($args[0]);
            if ($region === null) {
                $this->messenger->sendMessage($sender, "command.{$this->msg}.wrong-target", ["@region"], [$args[0]]);
                return;
            }
            $this->openUI($sender, $region);
        }
    }

    private function openUI(Player $target, Region $region): void
    {
        if (!$region->isLivesIn($target->getName()) && !$target->hasPermission("sregionprotector.info.other") && !$target->hasPermission("sregionprotector.admin")) {
            $this->messenger->sendMessage($target, "command.{$this->msg}.permission");
            return;
        }
        if ($this->uiType === UIType::CHEST) {
            ChestUIManager::open($target, $region);
        } else {
            FormUIManager::open($target, $region);
        }
    }
}
