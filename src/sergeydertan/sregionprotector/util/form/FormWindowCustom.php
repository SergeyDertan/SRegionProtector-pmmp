<?php
declare(strict_types=1);

namespace sergeydertan\sregionprotector\util\form;

use sergeydertan\sregionprotector\util\form\element\Element;
use sergeydertan\sregionprotector\util\form\element\ElementButtonImageData;
use sergeydertan\sregionprotector\util\form\element\ElementInput;
use sergeydertan\sregionprotector\util\form\element\ElementLabel;
use sergeydertan\sregionprotector\util\form\response\FormResponseCustom;

abstract class FormWindowCustom extends FormWindow
{

    public $type = "custom_form";

    /**
     * @var string
     */
    public $title;

    /**
     * @var ElementButtonImageData
     */
    public $icon;
    /**
     * @var Element[]
     */
    public $content;
    /**
     * @var
     */
    private $response;

    public function __construct(string $title, ?ElementButtonImageData $icon = null, array $content = [])
    {
        $this->title = $title;
        $this->icon = $icon;
        $this->content = $content;
    }

    public function getResponse(): ?FormResponseCustom
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
        $data = json_decode($data, true);

        $input = [];
        $responses = [];
        $label = [];

        $i = 0;

        foreach ($data as $elementData) {
            if ($i >= count($this->content)) break;
            if (!isset($this->content[$i])) break;
            $e = $this->content[$i];
            if ($e instanceof ElementLabel) {
                $label[$i] = $e->text;
                $responses[$i] = $e->text;
            } else if ($e instanceof ElementInput) {
                $input[$i] = $elementData;
                $responses[$i] = $elementData;
            }
            ++$i;
        }

        $this->response = new FormResponseCustom($responses, $input, $label);

        $this->closed = false;
    }
}
