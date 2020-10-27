<?php


namespace SchGroup\MyWarehouse\Jobs;


use App\Models\Orders\Order;
use Illuminate\Bus\Queueable;
use App\Models\Warehouse\WarehouseHistory;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use SchGroup\MyWarehouse\Loggers\MovementsLogger;
use App\Models\Warehouse\Bonus\WarehouseBonusHistory;
use SchGroup\MyWarehouse\Synchonizers\StockBalances\StockChangers\StockChangersManager;

class PerformChangeInMyWarehouseJob implements ShouldQueue
{
    use Queueable, Dispatchable;

    /**
     * @var Order
     */
    private $order;
    /**
     * @var WarehouseHistory|WarehouseBonusHistory
     */
    private $warehouseHistory;

    /**
     * PerformChangeInMyWarehouseJob constructor.
     * @param WarehouseHistory|WarehouseBonusHistory $warehouseHistory
     */
    public function __construct($warehouseHistory)
    {
        $this->warehouseHistory = $warehouseHistory;
    }

    /**
     * @param StockChangersManager $stockChangersManager
     * @param MovementsLogger $logger
     * @throws \Exception
     */
    public function handle(StockChangersManager $stockChangersManager, MovementsLogger $logger)
    {
        try {

            $stockChangersManager->synchronize($this->warehouseHistory);

        } catch (\Exception $exception) {
            $logger->error(
                "Stock changed by history: {$this->warehouseHistory->id} " . get_class($this->warehouseHistory)
                . " CODE: " .$exception->getCode() . " " . $exception->getMessage() . $exception->getTraceAsString()
            );
            throw $exception;
        }
    }
}