<?php
declare(strict_types=1);

namespace sergeydertan\sregionprotector\util\form\element;

class ElementButton
{
    /**
     * @var string
     */
    public $text;
    /**
     * @var ElementButtonImageData
     */
    public $image;

    public function __construct(string $text, ?ElementButtonImageData $image = null)
    {
        $this->text = $text;
        $this->image = $image;
    }
}
