<?php

namespace SchGroup\MyWarehouse\Jobs;

use App\Models\Orders\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use SchGroup\MyWarehouse\Loggers\OrderChangedLogger;
use SchGroup\MyWarehouse\Synchonizers\Helpers\OrderModifier;

class UpdateOrderInMyWarehouseJob implements ShouldQueue
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
     * @param OrderModifier $orderModifier
     * @param OrderChangedLogger $logger
     * @throws \MoySklad\Exceptions\EntityCantBeMutatedException
     * @throws \Throwable
     */
    public function handle(OrderModifier $orderModifier, OrderChangedLogger $logger)
    {
        try {

            $orderModifier->updateOrderInMyWarehouse($this->order);

        } catch (\Exception $exception) {
            $logger->error(
                "Update order: {$this->order->order_number} CODE: " . $exception->getCode() . " "
                . $exception->getMessage() . $exception->getTraceAsString()
            );
            throw $exception;
        }
    }
}