<?php

namespace SchGroup\MyWarehouse\Listeners;

use App\Events\Order\OrderWasUpdated;
use SchGroup\MyWarehouse\Jobs\UpdateOrderInMyWarehouseJob;

class MyWarehouseUpdateOrderListener
{
    /**
     * @param OrderWasUpdated $event
     */
    public function handle(OrderWasUpdated $event)
    {
        if ($this->isNeedToUpdateOrderInMyWarehouse($event)) {
            UpdateOrderInMyWarehouseJob::dispatch($event->order)->onQueue('orders');
        }
    }

    /**
     * @param OrderWasUpdated $event
     * @return bool
     */
    public function isNeedToUpdateOrderInMyWarehouse(OrderWasUpdated $event): bool
    {
        $order = $event->order;

        return isProduction() &&
            $event->orderStatusHasBeenChanged() &&
            !empty($order->getUuid());
    }
}