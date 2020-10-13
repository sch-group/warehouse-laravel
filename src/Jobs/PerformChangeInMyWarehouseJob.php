<?php


namespace SchGroup\MyWarehouse\Jobs;


use App\Models\Orders\Order;
use App\Models\Warehouse\WarehouseHistory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use SchGroup\MyWarehouse\Synchonizers\Helpers\Income\IncomingManager;

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
     * @param IncomingManager $incomingManager
     */
    public function handle(IncomingManager $incomingManager)
    {
        $incomingManager->synchronize($this->warehouseHistory);
    }
}