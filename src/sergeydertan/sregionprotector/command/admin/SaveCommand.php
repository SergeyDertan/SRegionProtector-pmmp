<?php
declare(strict_types=1);

namespace sergeydertan\sregionprotector\command\admin;

use pocketmine\command\CommandSender;
use sergeydertan\sregionprotector\command\SRegionProtectorCommand;
use sergeydertan\sregionprotector\main\SaveType;
use sergeydertan\sregionprotector\main\SRegionProtectorMain;

final class SaveCommand extends SRegionProtectorCommand
{
    /**
     * @var SRegionProtectorMain
     */
    private $pl;

    public function __construct(SRegionProtectorMain $pl)
    {
        parent::__construct("rgsave", "save");
        $this->pl = $pl;
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        if (!$this->checkPerm($sender)) return;
        $this->pl->save(SaveType::MANUAL, $sender->getName());
    }
}
