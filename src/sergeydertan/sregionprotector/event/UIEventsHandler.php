<?php
declare(strict_types=1);

namespace sergeydertan\sregionprotector\event;

use pocketmine\event\inventory\InventoryCloseEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\ModalFormResponsePacket;
use pocketmine\Player;
use sergeydertan\sregionprotector\ui\chest\ChestUIManager;
use sergeydertan\sregionprotector\ui\chest\UIInventory;
use sergeydertan\sregionprotector\ui\form\FormUIManager;
use sergeydertan\sregionprotector\ui\UIType;
use sergeydertan\sregionprotector\util\form\FormWindow;
use sergeydertan\sregionprotector\util\Tags;

final class UIEventsHandler implements Listener
{
    /**
     * @var int
     */
    private $uiType;
    /**
     * @var FormWindow[]
     */
    private $forms;

    public function __construct(int $uiType)
    {
        $this->uiType = $uiType;
    }

    /**
     * chest UI
     * @param InventoryTransactionEvent $e
     *
     * @priority HIGHEST
     * @ignoreCancelled true
     */
    public function inventoryTransaction(InventoryTransactionEvent $e): void
    {
        if ($this->uiType !== UIType::CHEST) return;

        $f = false;
        foreach ($e->getTransaction()->getInventories() as $inventory) {
            if ($inventory instanceof UIInventory) {
                $f = true;
                break;
            }
        }
        if (!$f) return;
        $e->setCancelled();
        foreach ($e->getTransaction()->getActions() as $action) {
            if ($action->getSourceItem()->getNamedTagEntry(Tags::IS_UI_ITEM_TAG)) {
                ChestUIManager::handle($e->getTransaction()->getSource(), $action->getSourceItem());
                return;
            }
            if ($action->getTargetItem()->getNamedTagEntry(Tags::IS_UI_ITEM_TAG)) {
                ChestUIManager::handle($e->getTransaction()->getSource(), $action->getTargetItem());
                return;
            }
        }
    }

    //chest UI
    public function inventoryClose(InventoryCloseEvent $e): void
    {
        if ($this->uiType !== UIType::CHEST) return;
        $inv = $e->getInventory();
        if (!$inv instanceof UIInventory) return;
        ChestUIManager::removeChest($e->getPlayer(), $inv->getHolder());
    }

    /**
     * form ui
     * @param DataPacketReceiveEvent $e
     *
     * @priority HIGHEST
     * @ignoreCancelled true
     */
    public function dataPacketReceive(DataPacketReceiveEvent $e): void
    {
        if ($this->uiType !== UIType::FORM) return;
        $pk = $e->getPacket();
        if (!$pk instanceof ModalFormResponsePacket) return;
        if (!isset($this->forms[spl_object_hash($e->getPlayer())])) return;
        $form = $this->forms[spl_object_hash($e->getPlayer())];
        if ($form->getId() !== $pk->formId) return;
        $form->setResponse($pk->formData);
        if ($form->wasClosed()) return;
        FormUIManager::handle($form, $e->getPlayer());
    }

    //form ui
    public function playerQuit(PlayerQuitEvent $e): void
    {
        if ($this->uiType !== UIType::FORM) return;
        unset($this->forms[spl_object_hash($e->getPlayer())]);
    }

    public function sendForm(FormWindow $form, Player $player): void
    {
        $this->forms[spl_object_hash($player)] = $form;
        $player->dataPacket($form->encode());
    }
}
