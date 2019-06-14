<?php
declare(strict_types=1);

namespace sergeydertan\sregionprotector\util;

class Task extends \pocketmine\scheduler\Task
{
    /**
     * @var callable
     */
    private $task;

    public function __construct(callable $task)
    {
        $this->task = $task;
    }

    public function onRun(int $currentTick)
    {
        ($this->task)();
    }
}
