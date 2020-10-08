<?php

namespace SchGroup\MyWarehouse\Listeners;

use App\Events\Order\OrderWasCreated;
use SchGroup\MyWarehouse\Jobs\CreateOrderInMyWarehouseJob;

class MyWarehouseCreateOrderListener
{
    /**
     * @param OrderWasCreated $orderWasCreated
     */
    public function handle(OrderWasCreated $orderWasCreated)
    {
        if (isProduction()) {
            CreateOrderInMyWarehouseJob::dispatch($orderWasCreated->order)->onQueue('orders');
        }
    }
}