<?php
declare(strict_types=1);

namespace sergeydertan\sregionprotector\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\plugin\Plugin;
use sergeydertan\sregionprotector\main\SRegionProtectorMain;

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
        if (!$this->checkPerm($sender)) return;
        if (empty($args) || strcasecmp($args[0], "help") === 0) {
            $this->messenger->sendMessage($sender, "command.{$this->msg}.available-commands");
            foreach ($this->commands as $name => $command) {
                if ($sender->hasPermission($command->getPermission())) $sender->sendMessage($name . " - " . $command->getDescription());
            }
            return;
        }
        if (!isset($this->commands[$args[0]])) {
            $this->messenger->sendMessage($sender, "command.{$this->msg}.command-doesnt-exists", ["@name"], [$args[0]]);
            return;
        }
        $this->commands[$args[0]]->execute($sender, array_shift($args), $args);
    }

    public function registerCommand(Command $command): void
    {
        $this->commands[str_replace("rg", "", str_replace("region", "", $command->getName()))] = $command;
    }

    public function getPlugin(): Plugin
    {
        return SRegionProtectorMain::getInstance();
    }
}
