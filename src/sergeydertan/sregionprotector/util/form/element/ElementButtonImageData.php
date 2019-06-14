<?php
declare(strict_types=1);

namespace sergeydertan\sregionprotector\util\form\element;

class ElementButtonImageData
{
    const IMAGE_TYPE_PATH = "path";
    const IMAGE_TYPE_URL = "url";

    /**
     * @var string
     */
    public $type;
    /**
     * @var string
     */
    public $data;

    public function __construct(string $type, string $data)
    {
        if ($type !== self::IMAGE_TYPE_PATH && !$type !== self::IMAGE_TYPE_URL) return;
        $this->type = $type;
        $this->data = $data;
    }
}
