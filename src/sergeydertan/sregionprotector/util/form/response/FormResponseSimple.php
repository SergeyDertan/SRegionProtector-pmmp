<?php
declare(strict_types=1);

namespace sergeydertan\sregionprotector\util\form\response;

use sergeydertan\sregionprotector\util\form\element\ElementButton;

class FormResponseSimple extends FormResponse
{
    /**
     * @var int
     */
    protected $clickedButtonId;
    /**
     * @var ElementButton|null
     */
    protected $clickedButton;

    public function __construct(int $clickedButtonId, ?ElementButton $clickedButton)
    {
        $this->clickedButtonId = $clickedButtonId;
        $this->clickedButton = $clickedButton;
    }

    public function getClickedButton(): ?ElementButton
    {
        return $this->clickedButton;
    }

    public function getClickedButtonId(): int
    {
        return $this->clickedButtonId;
    }
}
