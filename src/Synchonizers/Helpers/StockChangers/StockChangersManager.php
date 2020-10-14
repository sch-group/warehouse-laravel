<?php

namespace SchGroup\MyWarehouse\Synchonizers\Helpers\StockChangers;

use App\Models\Warehouse\WarehouseHistory;
use SchGroup\MyWarehouse\Contracts\StockChanger;

class StockChangersManager
{
    const INCOMING_CREATORS = [
       WarehouseHistory::ACTION_INCOMING => SupplyCreator::class,
       WarehouseHistory::ACTION_FIND => StockEnterCreator::class,
       WarehouseHistory::ACTION_INVENTORY => StockEnterCreator::class,
       WarehouseHistory::ACTION_LOSS => LossCreator::class,
    ];
    /**
     * @param WarehouseHistory $history
     */
    public function synchronize(WarehouseHistory $history): void
    {
        $stockChanger = $this->defineStockChanger($history);
        $stockChanger->createBy($history);
    }

    /**
     * @param WarehouseHistory $history
     * @return StockChanger
     */
    private function defineStockChanger(WarehouseHistory $history): StockChanger
    {
        return app(self::INCOMING_CREATORS[$history->action]);
    }
}