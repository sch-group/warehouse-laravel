<?php

namespace SchGroup\MyWarehouse\Listeners;

use App\Models\Orders\Order;
use Illuminate\Support\Collection;
use App\Services\Order\DTO\OrderDTO;
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
    private function isNeedToUpdateOrderInMyWarehouse(OrderWasUpdated $event): bool
    {
        $order = $event->order;
        $orderBeforeUpdate = $event->orderBeforeUpdate;

        return isProduction() &&
            ($event->orderStatusHasBeenChanged() || $this->itemsIsChanged($orderBeforeUpdate, $order)) &&
            !empty($order->getUuid());
    }

    /**
     * @param OrderDTO $orderBeforeUpdate
     * @param Order $order
     * @return bool
     */
    private function itemsIsChanged(OrderDTO $orderBeforeUpdate, Order $order): bool
    {
        $orderItemsIsChanged = $this->orderItemsIsChanged($orderBeforeUpdate->orderItems, $order->orderItems);
        $bonusesIsChanged = $this->bonusesIsChanged($orderBeforeUpdate->allBonuses, $order->allBonuses);

        return $orderItemsIsChanged || $bonusesIsChanged;
    }
    /**
     * @param Collection $itemsBefore
     * @param Collection $itemsAfter
     * @return bool
     */
    private function orderItemsIsChanged(Collection $itemsBefore, Collection $itemsAfter): bool
    {
        return $itemsBefore->sum('discounted_price') != $itemsAfter->sum('discounted_price');
    }

    /**
     * @param Collection $bonusesBefore
     * @param Collection $bonusesAfter
     * @return bool
     */
    private function bonusesIsChanged(Collection $bonusesBefore, Collection $bonusesAfter): bool
    {
        return $bonusesBefore->sum('quantity') != $bonusesAfter->sum('quantity');
    }

}