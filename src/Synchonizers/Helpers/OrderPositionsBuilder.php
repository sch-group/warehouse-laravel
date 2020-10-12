<?php


namespace SchGroup\MyWarehouse\Synchonizers\Helpers;

use MoySklad\MoySklad;
use App\Models\Orders\Order;
use App\Models\Orders\Status;
use MoySklad\Lists\EntityList;
use App\Models\Orders\OrderItem;
use MoySklad\Entities\Products\Variant;

class OrderPositionsBuilder
{
    /**
     * @var MoySklad
     */
    private $client;

    public function __construct(MoySklad $client)
    {
        $this->client = $client;
    }

    /**
     * @param Order $ourOrder
     * @return EntityList
     * @throws \Throwable
     */
    public function collectOrderPositions(Order $ourOrder): EntityList
    {
        $orderPositions = [];
        $itemsMustBeReserved = $this->isItemsMustBeReserved($ourOrder);
        $ourOrder->orderItems->each(function (OrderItem $orderItem) use (&$orderPositions, $itemsMustBeReserved) {
            $uuid = $orderItem->variant->getUuid();
            $remoteVariant = Variant::query($this->client)->byId($uuid);
            if ($remoteVariant) {
                $remoteVariant->quantity = $orderItem->quantity;
                $remoteVariant->reserve = $itemsMustBeReserved ? $orderItem->quantity : 0;
                $remoteVariant->overhead = $remoteVariant->quantity;
                $remoteVariant->price = (round($orderItem->discounted_price / $orderItem->quantity, 2) * 100);
                $orderPositions[] = $remoteVariant;
            }
        });

        return new EntityList($this->client, $orderPositions);
    }

    /**
     * @param Order $order
     * @return bool
     */
    private function isItemsMustBeReserved(Order $order) : bool
    {
        return in_array($order->status->code, Status::getReserveCodes());
    }
}