<?php
declare(strict_types=1);

namespace sergeydertan\sregionprotector\util\form;

use pocketmine\network\mcpe\protocol\ModalFormRequestPacket;

abstract class FormWindow
{
    /**
     * @var bool
     */
    protected $closed = false;

    public abstract function setResponse(string $data): void;

    public abstract function getResponse();

    public function wasClosed(): bool
    {
        return $this->closed;
    }

    public function encode(): ModalFormRequestPacket
    {
        $pk = new ModalFormRequestPacket();
        $pk->formId = $this->getId();
        $pk->formData = $this->getJSONData();
        return $pk;
    }

    public abstract function getId(): int;

    public function getJSONData(): string
    {
        return json_encode($this);
    }
}
