<?php
declare(strict_types=1);

namespace sergeydertan\sregionprotector\util\form\element;

class ElementLabel extends Element
{
    /**
     * @var string
     */
    public $type = "label";
    /**
     * @var string
     */
    public $text;

    public function __construct(string $text)
    {
        $this->text = $text;
    }
}
