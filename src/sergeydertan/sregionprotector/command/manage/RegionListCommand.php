<?php
declare(strict_types=1);

namespace sergeydertan\sregionprotector\command\manage;

use pocketmine\command\CommandSender;
use sergeydertan\sregionprotector\command\SRegionProtectorCommand;
use sergeydertan\sregionprotector\region\RegionGroup;
use sergeydertan\sregionprotector\region\RegionManager;

final class RegionListCommand extends SRegionProtectorCommand
{
    /**
     * @var RegionManager
     */
    private $regionManager;

    public function __construct(RegionManager $regionManager)
    {
        parent::__construct("rglist", "list");
        $this->regionManager = $regionManager;
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        if (!$this->checkPerm($sender)) return;
        if (!$this->checkUsage($sender, 1, $args)) return;
        if (!$this->isPlayer($sender)) return;

        $group = RegionGroup::valueOf($args[0]);
        if ($group === RegionGroup::INVALID) {
            $this->messenger->sendMessage($sender, "command.{$this->msg}.usage");
            return;
        }
        $regions = $this->regionManager->getPlayersRegionList($sender->getName(), $group);
        $list = [];
        foreach ($regions as $region) {
            $list[] = $region->getName();
        }
        switch ($group) {
            case RegionGroup::MEMBER:
                $this->messenger->sendMessage($sender, "command.{$this->msg}.member-region-list", ["@list"], [implode(", ", $list)]);
                break;
            case RegionGroup::OWNER:
                $this->messenger->sendMessage($sender, "command.{$this->msg}.owner-region-list", ["@list"], [implode(", ", $list)]);
                break;
            case RegionGroup::CREATOR:
                $this->messenger->sendMessage($sender, "command.{$this->msg}.creator-region-list", ["@list"], [implode(", ", $list)]);
                break;
        }
    }
}
