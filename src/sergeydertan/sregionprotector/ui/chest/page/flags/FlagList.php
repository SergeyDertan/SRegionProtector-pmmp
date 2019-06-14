<?php
declare(strict_types=1);

namespace sergeydertan\sregionprotector\ui\chest\page\flags;

use ArrayAccess;
use sergeydertan\sregionprotector\main\SRegionProtectorMain;
use sergeydertan\sregionprotector\region\Region;

class FlagList implements ArrayAccess
{
    /**
     * @var boolean[]
     */
    private static $display;
    /**
     * @var boolean[]
     */
    private static $status;
    /**
     * @var Region
     */
    private $region;
    /**
     * @var Flag[]
     */
    private $flags = [];

    public function __construct(Region $region)
    {
        $this->region = $region;

        self::$display = SRegionProtectorMain::getInstance()->getSettings()->getRegionSettings()->getDisplay();
        self::$status = SRegionProtectorMain::getInstance()->getSettings()->getRegionSettings()->getFlagStatus();
    }

    /**
     * @return Flag[]
     */
    public function getFlags(): array
    {
        return $this->flags;
    }

    public function load(bool $skipHidden = false): FlagList
    {
        $this->flags = [];
        $id = -1;
        foreach ($this->region->getFlags() as $flag) {
            ++$id;
            if ($skipHidden && (!self::$display[$id] || !self::$status[$id])) continue;
            $this->flags[] = new Flag($id, $flag);
        }
        return $this;
    }

    public function removeHidden(): FlagList
    {
        $this->flags = array_filter($this->flags, "isFlagHidden");
        return $this;
    }

    public function isFlagHidden(Flag $flag): bool
    {
        return !self::$display[$flag->getId()] || !self::$status[$flag->getId()];
    }

    public function offsetExists($offset): bool
    {
        return isset($this->flags[$offset]);
    }

    public function offsetGet($offset)
    {
        return isset($this->flags[$offset]) ? $this->flags[$offset] : null;
    }

    public function offsetSet($offset, $value): void
    {
        if (is_null($offset)) {
            $this->flags[] = $value;
        } else {
            $this->flags[$offset] = $value;
        }
    }

    public function offsetUnset($offset): void
    {
        unset($this->flags[$offset]);
    }
}
