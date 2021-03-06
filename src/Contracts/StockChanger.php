<?php


namespace SchGroup\MyWarehouse\Contracts;


use App\Models\Warehouse\WarehouseHistory;
use App\Models\Warehouse\Bonus\WarehouseBonusHistory;

interface StockChanger
{
    /**
     * @param WarehouseHistory|WarehouseBonusHistory $warehouseHistory
     * @param string $remoteChangeName
     */
    public function createBy($warehouseHistory, string $remoteChangeName): void;
}