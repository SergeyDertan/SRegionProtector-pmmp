<?php
declare(strict_types=1);

namespace sergeydertan\sregionprotector\command\manage\group;

use pocketmine\command\CommandSender;
use pocketmine\Player;
use sergeydertan\sregionprotector\command\SRegionProtectorCommand;
use sergeydertan\sregionprotector\region\RegionManager;

final class RemoveMemberCommand extends SRegionProtectorCommand
{
    private $regionManager;

    public function __construct(RegionManager $regionManager)
    {
        parent::__construct("rgremovemember", "removemember");
        $this->regionManager = $regionManager;
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        if (!$this->checkPerm($sender)) return;
        if (!$this->checkUsage($sender, 2, $args)) return;

        $region = $this->regionManager->getRegion($args[0]);
        if ($region === null) {
            $this->messenger->sendMessage($sender, "command.{$this->msg}.region-doesnt-exists", ["@region"], [$args[0]]);
            return;
        }

        $target = $args[1];
        if (strlen($target) === 0) {
            $this->messenger->sendMessage($sender, "command.{$this->msg}.usage");
            return;
        }
        if (($sender instanceof Player && !$region->isOwner($sender->getName(), true)) && !$sender->hasPermission("sregionprotector.admin")) {
            $this->messenger->sendMessage($sender, "command.{$this->msg}.permission");
            return;
        }
        if (!$region->isMember($target)) {
            $this->messenger->sendMessage($sender, "command.{$this->msg}.not-a-member", ["@region", "@target"], [$region->getName(), $target]);
            return;
        }
        $this->regionManager->removeMember($region, $target);
        $this->messenger->sendMessage($sender, "command.{$this->msg}.member-removed", ["@region", "@target"], [$region->getName(), $target]);
    }
}
