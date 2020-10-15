<?php


namespace SchGroup\MyWarehouse\Listeners;


use SchGroup\MyWarehouse\Events\MyWarehouseNeedChange;
use SchGroup\MyWarehouse\Synchonizers\StockBalances\StockChangers\StockChangersManager;

class MyWarehouseChangesListener
{
    /**
     * @var StockChangersManager
     */
    private $stockChangersManager;

    /**
     * MyWarehouseIncomingListener constructor.
     * @param StockChangersManager $stockChangersManager
     */
    public function __construct(StockChangersManager $stockChangersManager)
    {
        $this->stockChangersManager = $stockChangersManager;
    }
    /**
     * Execute
     * @param MyWarehouseNeedChange $event
     */
    public function handle(MyWarehouseNeedChange $event)
    {
        $this->stockChangersManager->synchronize($event->warehouseHistory);
    }
}