<?php

namespace SchGroup\MyWarehouse\Jobs;

use App\Models\Orders\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use SchGroup\MyWarehouse\Loggers\CreateOrderLogger;
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
     * @param CreateOrderLogger $logger
     * @throws \MoySklad\Exceptions\EntityCantBeMutatedException
     * @throws \MoySklad\Exceptions\IncompleteCreationFieldsException
     * @throws \Throwable
     */
    public function handle(OrderMaker $warehouseOrderMaker, CreateOrderLogger $logger)
    {
        try {
            $warehouseOrderMaker->createSingleOrder($this->order);

        } catch (\Exception $exception) {
            $logger->error(
                "Create order: {$this->order->order_number} CODE: " . $exception->getCode() . $exception->getMessage() . $exception->getTraceAsString()
            );
            throw $exception;
        }
    }
}