<?php
declare(strict_types=1);

namespace sergeydertan\sregionprotector\event;

use pocketmine\event\Cancellable;
use pocketmine\event\Event;

final class EmptyEvent extends Event implements Cancellable
{
}
