<?php


namespace SchGroup\MyWarehouse\Contracts;


use MoySklad\Lists\EntityList;
use App\Models\Warehouse\WarehouseHistory;
use App\Models\Warehouse\Bonus\WarehouseBonusHistory;

interface PositionBuilder
{
    /**
     * @param WarehouseHistory|WarehouseBonusHistory $warehouseHistory
     * @return mixed
     */
    public function buildPositionsBy($warehouseHistory) : EntityList;
}