<?php
declare(strict_types=1);

namespace sergeydertan\sregionprotector\util\form\element;

class ElementInput extends Element
{
    /**
     * @var string
     */
    public $type = "input";
    /**
     * @var string
     */
    public $text;
    /**
     * @var string
     */
    public $placeholder;
    /**
     * @var string
     */
    public $default;

    public function __construct(string $text, string $placeholder = "", string $default = "")
    {
        $this->text = $text;
        $this->placeholder = $placeholder;
        $this->default = $default;
    }
}
