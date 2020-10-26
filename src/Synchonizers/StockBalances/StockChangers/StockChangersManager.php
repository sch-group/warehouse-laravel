<?php

namespace SchGroup\MyWarehouse\Synchonizers\StockBalances\StockChangers;

use App\Models\Warehouse\WarehouseHistory;
use SchGroup\MyWarehouse\Contracts\StockChanger;
use App\Models\Warehouse\Bonus\WarehouseBonusHistory;

class StockChangersManager
{
    const INCOMING_CREATORS = [
       WarehouseHistory::ACTION_LOSS => LossCreator::class,
       WarehouseHistory::ACTION_FIND => InventoryEnterCreator::class,
       WarehouseHistory::ACTION_INVENTORY => InventoryEnterCreator::class,
       WarehouseHistory::ACTION_INCOMING => IncomingSupplyCreator::class,
    ];
    /**
     * @param WarehouseHistory|WarehouseBonusHistory $history
     */
    public function synchronize($history): void
    {
        $stockChanger = $this->defineStockChanger($history);
        $stockChanger->createBy($history);
    }

    /**
     * @param WarehouseHistory|WarehouseBonusHistory $history
     * @return StockChanger
     */
    private function defineStockChanger($history): StockChanger
    {
        return app(self::INCOMING_CREATORS[$history->action]);
    }
}