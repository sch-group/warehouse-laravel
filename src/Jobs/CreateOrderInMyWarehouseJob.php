<?php

namespace SchGroup\MyWarehouse\Jobs;

use App\Models\Orders\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use SchGroup\MyWarehouse\Synchonizers\Helpers\OrderMaker;

class CreateOrderInMyWarehouseJob implements ShouldQueue
{
    use Queueable, Dispatchable;

    /**
     * @var Order
     */
    private $order;

    /**
     * CreateOrderInMyWarehouse constructor.
     * @param Order $order
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * @param OrderMaker $warehouseOrderMaker
     * @throws \MoySklad\Exceptions\EntityCantBeMutatedException
     * @throws \MoySklad\Exceptions\IncompleteCreationFieldsException
     * @throws \Throwable
     */
    public function handle(OrderMaker $warehouseOrderMaker)
    {
        $warehouseOrderMaker->createSingleOrder($this->order);
    }
}