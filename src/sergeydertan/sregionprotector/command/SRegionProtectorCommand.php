<?php
declare(strict_types=1);

namespace sergeydertan\sregionprotector\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use sergeydertan\sregionprotector\main\SRegionProtectorMain;
use sergeydertan\sregionprotector\messenger\Messenger;

abstract class SRegionProtectorCommand extends Command implements PluginIdentifiableCommand
{
    /**
     * @var Messenger
     */
    protected $messenger;

    protected $msg;

    public function __construct(string $name, string $msg = null, string $perm = null)
    {
        if ($msg === null) {
            $msg = $name;
            $perm = $name;
        } else if ($perm === null) {
            $perm = $msg;
        }
        parent::__construct($name);
        $this->messenger = Messenger::getInstance();

        $this->msg = $msg;

        $this->setDescription($this->messenger->getMessage("command.$msg.description"));
        $this->setPermission("sregionprotector.command.$perm");
    }

    protected function checkPerm(CommandSender $target): bool
    {
        if ($this->testPermissionSilent($target)) {
            return true;
        }
        $this->messenger->sendMessage($target, "command.{$this->msg}.permission");
        return false;
    }

    protected function isPlayer(CommandSender $target): bool
    {
        if ($target instanceof Player) {
            return true;
        }
        $this->messenger->sendMessage($target, "command.{$this->msg}.in-game");
        return false;
    }

    protected function checkUsage(CommandSender $target, int $requiredArguments, array &$args): bool
    {
        if (count($args) >= $requiredArguments) return true;
        $this->messenger->sendMessage($target, "command.{$this->msg}.usage");
        return false;
    }

    public function getPlugin(): Plugin
    {
        return SRegionProtectorMain::getInstance();
    }
}
