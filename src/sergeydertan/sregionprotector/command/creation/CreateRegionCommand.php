<?php
declare(strict_types=1);

namespace sergeydertan\sregionprotector\command\creation;

use pocketmine\command\CommandSender;
use pocketmine\Player;
use sergeydertan\sregionprotector\command\SRegionProtectorCommand;
use sergeydertan\sregionprotector\region\RegionGroup;
use sergeydertan\sregionprotector\region\RegionManager;
use sergeydertan\sregionprotector\region\selector\RegionSelector;
use sergeydertan\sregionprotector\settings\RegionSettings;

final class CreateRegionCommand extends SRegionProtectorCommand
{
    /**
     * @var RegionSelector
     */
    private $regionSelector;
    /**
     * @var RegionManager
     */
    private $regionManager;
    /**
     * @var RegionSettings
     */
    private $regionSettings;

    public function __construct(RegionSelector $regionSelector, RegionManager $regionManager, RegionSettings $regionSettings)
    {
        parent::__construct("rgcreate", "create");

        $this->regionSelector = $regionSelector;
        $this->regionManager = $regionManager;
        $this->regionSettings = $regionSettings;
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        if (!$this->checkPerm($sender)) return;
        if (!$this->isPlayer($sender)) return;
        if (!$this->checkUsage($sender, 1, $args)) return;
        /**
         * @var Player $sender
         */
        $session = $this->regionSelector->getSession($sender);
        if ($session === null || $session->pos1 === null || $session->pos2 === null) {
            $this->messenger->sendMessage($sender, "command.{$this->msg}.two-positions-required");
            return;
        }
        $name = array_shift($args);
        $name = str_replace(" ", "", $name);
        if (strlen($name) === 0) {
            $r = [];
            $this->checkUsage($sender, 1, $r);
            return;
        }
        if (strlen($name) < $this->regionSettings->getMinRegionNameLength() || strlen($name) > $this->regionSettings->getMaxRegionNameLength() || !preg_match("/[A-Za-z0-9]+/", $name)) {
            $this->messenger->sendMessage($sender, "command.{$this->msg}.incorrect-name");
            return;
        }
        if ($this->regionManager->regionExists($name)) {
            $this->messenger->sendMessage($sender, "command.{$this->msg}.region-exists");
            return;
        }
        $pos1 = $session->pos1;
        $pos2 = $session->pos2;

        if ($pos1->level !== $pos2->level) {
            $this->messenger->sendMessage($sender, "command.{$this->msg}.positions-in-different-worlds");
            return;
        }
        if (!$this->regionSettings->hasAmountPermission($sender, $this->regionManager->getRegionAmount($sender->getName(), RegionGroup::CREATOR))) {
            $this->messenger->sendMessage($sender, "command.{$this->msg}.too-many");
            return;
        }
        if (!$this->regionSettings->hasSizePermission($sender, $session->calculateSize())) {
            $this->messenger->sendMessage($sender, "command.{$this->msg}.too-large");
            return;
        }
        if ($this->regionManager->checkOverlap($pos1, $pos2, $pos1->level->getName(), $sender->getName(), true)) {
            $this->messenger->sendMessage($sender, "command.{$this->msg}.regions-overlap");
            return;
        }
        $this->regionManager->createRegion($name, $sender->getName(), $pos1, $pos2, $pos1->level);
        $this->messenger->sendMessage($sender, "command.{$this->msg}.region-created", ["@region"], [$name]);
    }
}
