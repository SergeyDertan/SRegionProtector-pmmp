<?php
declare(strict_types=1);

namespace sergeydertan\sregionprotector\ui\form\element;

use sergeydertan\sregionprotector\ui\form\FormUIManager;
use sergeydertan\sregionprotector\ui\form\type\UIForm;
use sergeydertan\sregionprotector\util\form\element\ElementButton;
use sergeydertan\sregionprotector\util\form\element\ElementButtonImageData;

class Button extends ElementButton
{
    /**
     * @var string
     * target page class
     */
    private $target;
    //arguments for creating next page
    /**
     * @var array
     */
    private $args = [];
    /**
     * do something before opening next page
     * @var callable
     */
    private $beforeNext = null;

    //for remove region page, means that next page won`t be opened
    private $noNext = false;

    public function __construct(string $text, string $next, ...$args)
    {
        parent::__construct($text);

        $this->target = $next;
        $this->args = $args;
    }

    public function setBeforeNext(?callable $beforeNext): self
    {
        $this->beforeNext = $beforeNext;
        return $this;
    }

    public function noNext(bool $noNext): self
    {
        $this->noNext = $noNext;
        return $this;
    }

    public function getNext(): ?UIForm
    {
        if ($this->beforeNext !== null) ($this->beforeNext)();
        if ($this->noNext) return null;
        return FormUIManager::getPageInstance($this->target, ...$this->args);
    }

    public function setImage(ElementButtonImageData $image): self
    {
        $this->image = $image;
        return $this;
    }
}
