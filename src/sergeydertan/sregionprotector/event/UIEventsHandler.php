<?php
declare(strict_types=1);

namespace sergeydertan\sregionprotector\event;

use pocketmine\event\inventory\InventoryCloseEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use sergeydertan\sregionprotector\ui\chest\ChestUIManager;
use sergeydertan\sregionprotector\ui\chest\UIInventory;
use sergeydertan\sregionprotector\ui\UIType;
use sergeydertan\sregionprotector\util\Tags;

final class UIEventsHandler implements Listener
{
    /**
     * @var int
     */
    private $uiType;

    public function __construct(int $uiType)
    {
        $this->uiType = $uiType;
    }

    /**
     * @param InventoryTransactionEvent $e
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

    public function inventoryClose(InventoryCloseEvent $e): void
    {
        if ($this->uiType !== UIType::CHEST) return;
        $inv = $e->getInventory();
        if (!$inv instanceof UIInventory) return;
        ChestUIManager::removeChest($e->getPlayer(), $inv->getHolder());
    }
}
