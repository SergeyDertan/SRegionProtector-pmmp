<?php
declare(strict_types=1);

namespace sergeydertan\sregionprotector\command\manage;

use pocketmine\command\CommandSender;
use pocketmine\Player;
use sergeydertan\sregionprotector\command\SRegionProtectorCommand;
use sergeydertan\sregionprotector\region\RegionManager;

final class SetPriorityCommand extends SRegionProtectorCommand
{
    /**
     * @var RegionManager
     */
    private $regionManager;
    /**
     * @var bool
     */
    private $prioritySystem;

    public function __construct(RegionManager $regionManager, bool $prioritySystem)
    {
        parent::__construct("rgsetpriority", "set-priority");
        $this->regionManager = $regionManager;
        $this->prioritySystem = $prioritySystem;
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        if (!$this->checkPerm($sender)) return;
        if (!$this->checkUsage($sender, 2, $args)) return;
        $region = $this->regionManager->getRegion($args[0]);
        if ($region === null) {
            $this->messenger->sendMessage($sender, "command.{$this->msg}.wrong-target");
            return;
        }
        if ($sender instanceof Player && !$sender->hasPermission("sregionprotector.admin") && !$region->isCreator($sender->getName())) {
            $this->messenger->sendMessage($sender, "command.{$this->msg}.permission");
            return;
        }
        $priority = (int)$args[1];
        $region->setPriority($priority);
        $this->messenger->sendMessage($sender, "command.{$this->msg}.success", ["@region", "@priority"], [$region->getName(), (string)$priority]);
        if (!$this->prioritySystem) $this->messenger->sendMessage($sender, "command.{$this->msg}.warning");
    }
}
