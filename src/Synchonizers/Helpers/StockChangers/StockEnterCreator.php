<?php


namespace SchGroup\MyWarehouse\Synchonizers\Helpers\StockChangers;


use MoySklad\MoySklad;
use App\Models\Warehouse\WarehouseHistory;
use SchGroup\MyWarehouse\Synchonizers\Helpers\StoreDataKeeper;

class StockEnterCreator extends StockChanger
{
    /**
     * @var MoySklad
     */
    protected $client;
    /**
     * @var StoreDataKeeper
     */
    protected $storeDataKeeper;

    /**
     * @param WarehouseHistory $warehouseHistory
     */
    public function createBy(WarehouseHistory $warehouseHistory): void
    {
        // TODO: Implement createBy() method.
    }
}