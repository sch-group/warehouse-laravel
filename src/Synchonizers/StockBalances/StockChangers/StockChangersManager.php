<?php

namespace SchGroup\MyWarehouse\Synchonizers\StockBalances\StockChangers;

use App\Models\Warehouse\WarehouseHistory;
use SchGroup\MyWarehouse\Contracts\StockChanger;
use App\Models\Warehouse\Bonus\WarehouseBonusHistory;

class StockChangersManager
{
    const STOCK_CHANGE_CREATORS = [
       WarehouseHistory::ACTION_LOSS => LossCreator::class,
       WarehouseHistory::ACTION_FIND => FindingEnterCreator::class,
       WarehouseHistory::ACTION_INVENTORY => FindingEnterCreator::class,
       WarehouseHistory::ACTION_INCOMING => IncomingSupplyCreator::class,
    ];

    const CHANGER_NAMES = [
        WarehouseHistory::class => "variants",
        WarehouseBonusHistory::class => "bonuses",
    ];

    /**
     * @param WarehouseHistory|WarehouseBonusHistory $history
     */
    public function synchronize($history): void
    {
        $stockChanger = $this->defineStockChanger($history);
        $remoteChangeName = $this->defineRemoteChangeName($history);

        $stockChanger->createBy($history, $remoteChangeName);
    }

    /**
     * @param WarehouseHistory|WarehouseBonusHistory $history
     * @return StockChanger
     */
    private function defineStockChanger($history): StockChanger
    {
        return app(self::STOCK_CHANGE_CREATORS[$history->action]);
    }

    /**
     * @param WarehouseHistory|WarehouseBonusHistory $history
     * @return string
     */
    private function defineRemoteChangeName($history): string
    {
        return $history->id . "_" . $history->action . "_" . self::CHANGER_NAMES[get_class($history)];
    }
}