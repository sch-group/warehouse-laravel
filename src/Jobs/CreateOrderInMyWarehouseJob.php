<?php

namespace SchGroup\MyWarehouse\Jobs;

use App\Models\Orders\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use SchGroup\MyWarehouse\Synchonizers\Helpers\WarehouseOrderMaker;

class CreateOrderInMyWarehouseJob implements ShouldQueue
{
    use Queueable, Dispatchable;

    /**
     * @var Order
     */
    private $order;

    /**
     * CreateOrderInMyWarehouse constructor.
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * @param WarehouseOrderMaker $warehouseOrderMaker
     * @throws \MoySklad\Exceptions\EntityCantBeMutatedException
     * @throws \MoySklad\Exceptions\IncompleteCreationFieldsException
     * @throws \Throwable
     */
    public function handle(WarehouseOrderMaker $warehouseOrderMaker)
    {
        $warehouseOrderMaker->createSingleOrder($this->order);
    }
}