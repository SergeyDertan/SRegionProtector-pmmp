<?php
declare(strict_types=1);

namespace sergeydertan\sregionprotector\event;

use pocketmine\event\Listener;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\SetLocalPlayerAsInitializedPacket;
use pocketmine\utils\TextFormat;
use sergeydertan\sregionprotector\messenger\Messenger;
use sergeydertan\sregionprotector\util\Pair;

final class NotifierEventsHandler implements Listener
{
    /**
     * @var Pair
     */
    private $updateInfo;

    public function __construct(string $version, string $description)
    {
        $version = TextFormat::GREEN . Messenger::getInstance()->getMessage("loading.init.update-available", ["@ver"], [$version]);
        $description = TextFormat::GREEN . Messenger::getInstance()->getMessage("loading.init.update-description", ["@description"], [$description]);

        $this->updateInfo = new Pair($version, $description);
    }

    public function playerJoin(DataPacketReceiveEvent $e): void
    {
        if ($e->getPacket() instanceof SetLocalPlayerAsInitializedPacket) {
            $e->getPlayer()->sendMessage($this->updateInfo->getFirst());
            $e->getPlayer()->sendMessage($this->updateInfo->getSecond());
        }
    }
}
