<?php
declare(strict_types=1);

namespace sergeydertan\sregionprotector\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;

final class RegionCommand extends SRegionProtectorCommand
{
    /**
     * name -> command
     * @var Command[]
     */
    private $commands;

    public function __construct()
    {
        parent::__construct("region");

        $this->setAliases(["rg"]);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
    }

    public function registerCommand(Command $command): void
    {
        $this->commands[str_replace("rg", "", str_replace("region", "", $command->getName()))] = $command;
    }
}
