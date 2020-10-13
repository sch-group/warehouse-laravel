<?php

namespace SchGroup\MyWarehouse\Events;

use App\Models\Warehouse\WarehouseHistory;

class MyWarehouseNeedChange
{
    /**
     * @var WarehouseHistory
     */
    public $warehouseHistory;

    /**
     * MyWarehouseChanger constructor.
     * @param WarehouseHistory $warehouseHistory
     */
    public function __construct(WarehouseHistory $warehouseHistory)
    {
        $this->warehouseHistory = $warehouseHistory;
    }
}