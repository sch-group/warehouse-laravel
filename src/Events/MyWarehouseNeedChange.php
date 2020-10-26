<?php

namespace SchGroup\MyWarehouse\Events;

use App\Models\Warehouse\WarehouseHistory;
use App\Models\Warehouse\Bonus\WarehouseBonusHistory;

class MyWarehouseNeedChange
{
    /**
     * @var WarehouseHistory
     */
    public $warehouseHistory;

    /**
     * MyWarehouseChanger constructor.
     * @param WarehouseHistory|WarehouseBonusHistory $warehouseHistory
     */
    public function __construct($warehouseHistory)
    {
        $this->warehouseHistory = $warehouseHistory;
    }
}