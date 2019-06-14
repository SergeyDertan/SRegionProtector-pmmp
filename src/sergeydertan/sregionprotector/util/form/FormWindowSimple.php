<?php
declare(strict_types=1);

namespace sergeydertan\sregionprotector\util\form;

use sergeydertan\sregionprotector\util\form\element\ElementButton;
use sergeydertan\sregionprotector\util\form\response\FormResponseSimple;

abstract class FormWindowSimple extends FormWindow
{
    public $type = "form";

    /**
     * @var string
     */
    public $title;
    /**
     * @var string
     */
    public $content;
    /**
     * @var ElementButton[]
     */
    public $buttons;

    /**
     * @var FormResponseSimple
     */
    protected $response = null;

    public function __construct(string $title, string $content = "", array $buttons = [])
    {
        $this->title = $title;
        $this->content = $content;
        $this->buttons = $buttons;
    }

    public function getResponse(): ?FormResponseSimple
    {
        return $this->response;
    }

    public function setResponse(string $data): void
    {
        $data = strtolower($data);
        $data = str_replace(" ", "", $data);
        $data = str_replace("\n", "", $data);
        if ($data === "null") {
            $this->closed = true;
            return;
        }
        if (!is_numeric($data)) return;
        $data = (int)$data;
        if ($data >= count($this->buttons)) {
            $this->response = new FormResponseSimple($data, null);
            return;
        }
        $this->response = new FormResponseSimple($data, $this->buttons[$data]);

        $this->closed = false;
    }
}
