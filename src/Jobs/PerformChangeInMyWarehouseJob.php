<?php


namespace SchGroup\MyWarehouse\Jobs;


use App\Models\Orders\Order;
use Illuminate\Bus\Queueable;
use App\Models\Warehouse\WarehouseHistory;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use SchGroup\MyWarehouse\Synchonizers\StockBalances\StockChangers\StockChangersManager;

class PerformChangeInMyWarehouseJob implements ShouldQueue
{
    use Queueable, Dispatchable;

    /**
     * @var Order
     */
    private $order;
    /**
     * @var WarehouseHistory
     */
    private $warehouseHistory;

    /**
     * PerformChangeInMyWarehouseJob constructor.
     * @param WarehouseHistory $warehouseHistory
     */
    public function __construct(WarehouseHistory $warehouseHistory)
    {
        $this->warehouseHistory = $warehouseHistory;
    }

    /**
     * @param StockChangersManager $stockChangersManager
     */
    public function handle(StockChangersManager $stockChangersManager)
    {
        $stockChangersManager->synchronize($this->warehouseHistory);
    }
}